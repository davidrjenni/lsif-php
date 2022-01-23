<?php

declare(strict_types=1);

namespace LsifPhp\Types;

use LsifPhp\Parser\NodeTraverserFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;

/** DefinitionCollector collects identifier definition information from AST nodes. */
final class DefinitionCollector
{
    private NodeTraverserFactory $nodeTraverserFactory;

    /** @var Definition[] */
    private array $definitions;

    public function __construct()
    {
        $this->nodeTraverserFactory = new NodeTraverserFactory();
        $this->definitions = [];
    }

    /**
     * Returns all collected definitions.
     * NOTE: Call `collect` first, otherwise the result is empty.
     *
     * @return Definition[]
     */
    public function definitions(): array
    {
        return $this->definitions;
    }

    /**
     * Collects all definitions from the given statements.
     *
     * @param  Stmt[]  $stmts
     */
    public function collect(int $docId, array $stmts): void
    {
        $this->nodeTraverserFactory
            ->create(
                $this,
                function (Node $node) use ($docId): void {
                    $this->collectDefinition($docId, $node);
                }
            )
            ->traverse($stmts);
    }

    private function collectDefinition(int $docId, Node $node): void
    {
        if ($node instanceof ClassLike && $node->name !== null) {
            $this->collectClassLikeDefinition($docId, $node);
        } elseif ($node instanceof ClassConst) {
            foreach ($node->consts as $const) {
                $this->collectClassConst($docId, $node, $const);
            }
        } elseif ($node instanceof Property) {
            foreach ($node->props as $prop) {
                $this->collectProperty($docId, $node, $prop);
            }
        } elseif ($node instanceof ClassMethod) {
            $this->collectMethod($docId, $node);
        } elseif ($node instanceof Param) {
            $this->collectParam($docId, $node);
        } elseif ($node instanceof Assign) {
            $this->collectAssign($docId, $node);
        } elseif ($node instanceof Foreach_) {
            $this->collectForeach($docId, $node);
        }
    }

    private function collectClassLikeDefinition(int $docId, ClassLike $classLike): void
    {
        $this->definitions[] = new Definition(
            $docId,
            $classLike->name,
            IdentifierBuilder::fqClassName($classLike),
            $classLike,
            true,
            $classLike->getDocComment()
        );
    }

    private function collectClassConst(int $docId, ClassConst $classConst, Const_ $const): void
    {
        $this->definitions[] = new Definition(
            $docId,
            $const->name,
            IdentifierBuilder::fqName($classConst, $const->name->toString()),
            $const,
            !$classConst->isPrivate(),
            $classConst->getDocComment()
        );
    }

    private function collectProperty(int $docId, Property $property, PropertyProperty $prop): void
    {
        $this->definitions[] = new Definition(
            $docId,
            $prop->name,
            IdentifierBuilder::fqName($property, $prop->name->toString()),
            $prop,
            !$property->isPrivate(),
            $property->getDocComment()
        );
    }

    private function collectMethod(int $docId, ClassMethod $method): void
    {
        $this->definitions[] = new Definition(
            $docId,
            $method->name,
            IdentifierBuilder::fqName($method, "{$method->name}()"),
            $method,
            !$method->isPrivate(),
            $method->getDocComment()
        );
    }

    private function collectParam(int $docId, Param $param): void
    {
        $name = new Identifier($param->var->name, $param->getAttributes());

        // Handle constructor property promotion.
        if ($param->flags !== 0) {
            $this->definitions[] = new Definition(
                $docId,
                $name,
                IdentifierBuilder::fqName($param->getAttribute('parent'), $name->toString()),
                $param,
                !((bool) ($param->flags & Class_::MODIFIER_PRIVATE)),
                $param->getDocComment()
            );
        }

        $this->definitions[] = new Definition(
            $docId,
            $name,
            IdentifierBuilder::fqName($param, $name->toString()),
            $param,
            false,
            $param->getDocComment()
        );
    }

    private function collectAssign(int $docId, Assign $assign): void
    {
        if ($assign->var instanceof Variable) {
            $this->collectVar($docId, $assign->var, $assign->getDocComment());
        }
    }

    private function collectForeach(int $docId, Foreach_ $f): void
    {
        if ($f->keyVar !== null && $f->keyVar instanceof Variable) {
            $this->collectVar($docId, $f->keyVar);
        }
        if ($f->valueVar instanceof Variable) {
            $this->collectVar($docId, $f->valueVar);
        }
    }

    private function collectVar(int $docId, Variable $var, ?Doc $doc = null): void
    {
        if (!is_string($var->name)) {
            return;
        }
        $fqName = IdentifierBuilder::fqName($var, $var->name);
        $this->definitions[] = new Definition($docId, $var, $fqName, $var, false, $doc);
    }
}
