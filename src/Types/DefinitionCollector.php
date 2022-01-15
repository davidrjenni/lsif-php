<?php

declare(strict_types=1);

namespace LsifPhp\Types;

use LsifPhp\Parser\NodeTraverserFactory;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;

/** DefinitionCollector collects identifier definition information from AST nodes. */
final class DefinitionCollector
{

    private NodeTraverserFactory $nodeTraverserFactory;

    /** @var Definition[] */
    private array $definitions = [];

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
    public function collect(array $stmts): void
    {
        $this->nodeTraverserFactory
            ->create(
                $this,
                function (Node $node): void {
                    $this->collectDefinition($node);
                }
            )
            ->traverse($stmts);
    }

    public function collectDefinition(Node $node): void
    {
        if ($node instanceof ClassLike && $node->name !== null) {
            $this->collectClassLikeDefinition($node);
        } elseif ($node instanceof ClassConst) {
            foreach ($node->consts as $const) {
                $this->collectClassConst($node, $const);
            }
        } elseif ($node instanceof Property) {
            foreach ($node->props as $prop) {
                $this->collectProperty($node, $prop);
            }
        } elseif ($node instanceof ClassMethod) {
            $this->collectMethod($node);
        } elseif ($node instanceof Param) {
            $this->collectParam($node);
        } elseif ($node instanceof Assign) {
            $this->collectAssign($node);
        }
    }

    private function collectClassLikeDefinition(ClassLike $classLike): void
    {
        if (!isset($classLike->namespacedName)) {
            return;
        }

        $this->definitions[] = new Definition(
            $classLike->name,
            $classLike->namespacedName->toString(),
            $classLike,
            true,
            $classLike->getDocComment()
        );
    }

    private function collectClassConst(ClassConst $classConst, Const_ $const): void
    {
        $this->definitions[] = new Definition(
            $const->name,
            $this->fqName($classConst, $const->name->toString()),
            $classConst,
            !$classConst->isPrivate(),
            $classConst->getDocComment()
        );
    }

    private function collectProperty(Property $property, PropertyProperty $prop): void
    {
        $this->definitions[] = new Definition(
            $prop->name,
            $this->fqName($property, $prop->name->toString()),
            $property,
            !$property->isPrivate(),
            $property->getDocComment()
        );
    }

    private function collectMethod(ClassMethod $method): void
    {
        $this->definitions[] = new Definition(
            $method->name,
            $this->fqName($method, $method->name->toString()),
            $method,
            !$method->isPrivate(),
            $method->getDocComment()
        );
    }

    private function collectParam(Param $param): void
    {
        $name = new Identifier($param->var->name, $param->getAttributes());

        // Handle constructor property promotion.
        if ($param->flags !== 0) {
            $this->definitions[] = new Definition(
                $name,
                $this->fqName($param->getAttribute('parent'), $name->toString()),
                $param,
                !((bool) ($param->flags & Class_::MODIFIER_PRIVATE)),
                $param->getDocComment()
            );
        }

        $this->definitions[] = new Definition(
            $name,
            $this->fqName($param, $name->toString()),
            $param,
            false,
            $param->getDocComment()
        );
    }

    private function collectAssign(Assign $assign): void
    {
        if (!$assign->var instanceof Variable) {
            return;
        }

        $this->definitions[] = new Definition(
            $assign->var,
            $this->fqName($assign, $assign->var->name),
            $assign->var,
            false,
            $assign->getDocComment()
        );
    }

    private function fqName(Node $node, string $name): string
    {
        $node = $node->getAttribute('parent');
        if ($node === null) {
            return $name;
        }

        if ($node instanceof FunctionLike) {
            $name = "{$this->functionLikeName($node)}::$name";
        } else if ($node instanceof ClassLike) {
            $name = "{$this->classLikeName($node)}::$name";
        }
        return $this->fqName($node, $name);
    }

    private function classLikeName(ClassLike $node): string
    {
        return !isset($node->namespacedName)
            ? "anon-class-{$node->getStartTokenPos()}"
            : $node->namespacedName->toString();
    }

    private function functionLikeName(FunctionLike $node): string
    {
        return !isset($node->name)
            ? "anon-func-{$node->getStartTokenPos()}"
            : $node->name->toString();
    }
}
