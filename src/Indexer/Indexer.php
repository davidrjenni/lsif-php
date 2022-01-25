<?php

declare(strict_types=1);

namespace LsifPhp\Indexer;

use Closure;
use LsifPhp\Composer\Composer;
use LsifPhp\File\FileReader;
use LsifPhp\Parser\NodeTraverserFactory;
use LsifPhp\Parser\ParserFactory;
use LsifPhp\Protocol\Emitter;
use LsifPhp\Protocol\HoverResultContent;
use LsifPhp\Protocol\Pos;
use LsifPhp\Protocol\ToolInfo;
use LsifPhp\Types\DefinitionCollector;
use LsifPhp\Types\IdentifierBuilder;
use LsifPhp\Types\TypeCollector;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Parser;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_merge;
use function array_unique;
use function count;

use const DIRECTORY_SEPARATOR;

final class Indexer
{
    private const LANGUAGE_PHP = 'php';

    private Parser $parser;

    private NodeTraverserFactory $nodeTraverserFactory;

    private DefinitionCollector $definitionCollector;

    private TypeCollector $typeCollector;

    private Composer $composer;

    private int $projectId;

    /** @var string[] */
    private array $files;

    /** @var array<int, Document> */
    private array $documents;

    /** @var array<string, Definition> */
    private array $definitions;

    public function __construct(
        private string $projectRoot,
        private Emitter $emitter,
        private ToolInfo $toolInfo,
    ) {
        $this->parser = ParserFactory::create();
        $this->nodeTraverserFactory = new NodeTraverserFactory();
        $this->definitionCollector = new DefinitionCollector();
        $this->typeCollector = new TypeCollector();
        $this->projectId = -1;
        $this->files = [];
        $this->documents = [];
        $this->definitions = [];
    }

    public function index(): void
    {
        $this->loadProjectFiles();
        $this->emitMetaDataAndProject();
        $this->emitDocuments();
        $this->emitDefinitions();
        $this->emitReferences();
        $this->linkItemsToDefinitions();
        $this->emitContains();
    }

    private function loadProjectFiles(): void
    {
        $this->composer = Composer::parse($this->projectRoot);

        foreach ($this->composer->sourceDirs() as $dir) {
            $fileIterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->projectRoot . DIRECTORY_SEPARATOR . $dir)
            );

            /** @var SplFileInfo $f */
            foreach ($fileIterator as $f) {
                if ($f->getExtension() === 'php') {
                    $this->files[] = $f->getRealPath();
                }
            }
        }
    }

    private function emitMetaDataAndProject(): void
    {
        $this->emitter->emitMetaData($this->projectRoot, $this->toolInfo);
        $this->projectId = $this->emitter->emitProject(self::LANGUAGE_PHP);
    }

    private function emitDocuments(): void
    {
        foreach ($this->files as $path) {
            $code = FileReader::read($path);
            $stmts = $this->parser->parse($code);
            $id = $this->emitter->emitDocument($path, self::LANGUAGE_PHP);
            $doc = new Document($id, $path, $code, $stmts);
            $this->documents[$id] = $doc;
            $this->definitionCollector->collect($id, $stmts);
        }
        $defs = $this->definitionCollector->definitions();
        $this->typeCollector->collect($defs);
    }

    private function emitDefinitions(): void
    {
        $defs = $this->definitionCollector->definitions();
        foreach ($defs as $def) {
            $d = $this->emitDefinition(
                $def->name(),
                $this->documents[$def->docId()],
                HoverContent::create($def, Indexer::LANGUAGE_PHP)
            );
            $this->definitions[$def->identifier()] = $d;
        }
    }

    /** @param  HoverResultContent[]  $hoverContent */
    private function emitDefinition(Node $node, Document $doc, array $hoverContent): Definition
    {
        $rangeId = $this->emitRange($node, $doc);
        $doc->addDefinitionRangeId($rangeId);

        $resultSetId = $this->emitter->emitResultSet();
        $definitionResultId = $this->emitter->emitDefinitionResult();

        $this->emitter->emitNext($rangeId, $resultSetId);
        $this->emitter->emitTextDocumentDefinition($resultSetId, $definitionResultId);
        $this->emitter->emitItem($definitionResultId, [$rangeId], $doc->id());

        $hoverResultId = $this->emitter->emitHoverResult($hoverContent);
        $this->emitter->emitTextDocumentHover($resultSetId, $hoverResultId);

        return new Definition($doc->id(), $rangeId, $resultSetId);
    }

    private function emitReferences(): void
    {
        $this->traverseDocumentNodes(
            function (Node $node, Document $doc): void {
                if ($node instanceof ClassConstFetch && $node->class instanceof Name) {
                    $fqClassName = IdentifierBuilder::fqClassName($node->class);
                    $fqName = "{$fqClassName}::{$node->name}";
                    $this->tryEmitReference($fqName, $doc, $node->name);
                    return;
                }

                if (
                    ($node instanceof MethodCall || $node instanceof NullsafeMethodCall)
                    && $node->name instanceof Identifier
                ) {
                    $types = $this->typeCollector->typeExpr($node->var);
                    foreach ($types as $type) {
                        $fqName = "{$type}::{$node->name}()";
                        $this->tryEmitReference($fqName, $doc, $node->name);
                    }
                    return;
                }

                if (
                    ($node instanceof PropertyFetch || $node instanceof NullsafePropertyFetch)
                    && $node->name instanceof Identifier
                ) {
                    $types = $this->typeCollector->typeExpr($node->var);
                    foreach ($types as $type) {
                        $fqName = "{$type}::{$node->name}";
                        $this->tryEmitReference($fqName, $doc, $node->name);
                    }
                    return;
                }

                if ($node instanceof Name) {
                    $fqName = IdentifierBuilder::fqClassName($node);
                    $this->tryEmitReference($fqName, $doc, $node);
                    return;
                }

                if ($node instanceof StaticCall && $node->class instanceof Name && $node->name instanceof Identifier) {
                    $fqClassName = IdentifierBuilder::fqClassName($node->class);
                    $fqName = "{$fqClassName}::{$node->name}()";
                    $this->tryEmitReference($fqName, $doc, $node->name);
                    return;
                }

                if ($node instanceof StaticPropertyFetch && $node->class instanceof Name && $node->name instanceof Identifier) {
                    $fqClassName = IdentifierBuilder::fqClassName($node->class);
                    $fqName = "{$fqClassName}::{$node->name}";
                    $this->tryEmitReference($fqName, $doc, $node->name);
                    return;
                }

                if ($node instanceof Variable && !($node->getAttribute('parent') instanceof Param)) {
                    $fqName = IdentifierBuilder::fqName($node, $node->name);
                    $this->tryEmitReference($fqName, $doc, $node);
                    return;
                }
            }
        );
    }

    private function tryEmitReference(string $fqName, Document $doc, Identifier|Name|Variable $node): void
    {
        $def = $this->definitions[$fqName] ?? null;
        if ($def !== null) {
            $this->emitReference($doc, $node, $def);
        }
    }

    private function emitReference(Document $doc, Identifier|Name|Variable $name, ?Definition $def): void
    {
        if ($def === null) {
            return;
        }

        $rangeId = $this->emitRange($name, $doc);
        $doc->addReferenceRangeId($rangeId);
        $this->emitter->emitNext($rangeId, $def->resultSetId());
        $def->addReferenceRangeId($doc->id(), $rangeId);
    }

    private function linkItemsToDefinitions(): void
    {
        foreach ($this->definitions as $def) {
            $this->linkItemToDefinition($def);
        }
    }

    private function linkItemToDefinition(Definition $def): void
    {
        $refResultId = $this->emitter->emitReferenceResult();
        $this->emitter->emitTextDocumentReference($def->resultSetId(), $refResultId);
        $this->emitter->emitItemOfDefinitions($refResultId, [$def->rangeId()], $def->docId());

        foreach ($def->referenceRangeIds() as $docId => $rangeIds) {
            $this->emitter->emitItemOfReferences($refResultId, $rangeIds, $docId);
        }
    }

    private function emitContains(): void
    {
        foreach ($this->documents as $doc) {
            if (count($doc->definitionRangeIds()) > 0 || count($doc->referenceRangeIds()) > 0) {
                $this->emitter->emitContains(
                    $doc->id(),
                    array_unique(array_merge($doc->definitionRangeIds(), $doc->referenceRangeIds()))
                );
            }
        }
        $this->emitter->emitContains($this->projectId, array_keys($this->documents));
    }

    /** @param  Closure(Node, Document): void  $visitor */
    private function traverseDocumentNodes(Closure $visitor): void
    {
        foreach ($this->documents as $doc) {
            $this->nodeTraverserFactory
                ->create($this, $visitor, $doc)
                ->traverse($doc->stmts());
        }
    }

    private function emitRange(Node $node, Document $doc): int
    {
        return $this->emitter->emitRange(
            Pos::start($node, $doc->code()),
            Pos::end($node, $doc->code())
        );
    }
}
