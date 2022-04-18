<?php

declare(strict_types=1);

namespace LsifPhp\Types\Internal;

use LsifPhp\Types\Definition;
use LsifPhp\Types\IdentifierBuilder;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Clone_;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\MatchArm;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;

use function array_map;
use function array_merge;

/**
 * TypeCollector collects types of expressions which evaluate to named types.
 * @internal
 */
final class TypeCollector
{
    private TypeMap $types;

    public function __construct()
    {
        $this->types = new TypeMap();
    }

    /**
     * Returns all fully-qualified names of "upper" class-likes, e.g. parent classes, interfaces or traits.
     *
     * @param  string[]  $classes
     * @return string[]
     */
    public function uppers(array $classes): array
    {
        return $this->types->uppers($classes);
    }

    /**
     * Returns the fully qualified names of the types to which the expression
     * can evaluate to, or an empty array for unknown or unnamed types.
     * NOTE: call `collect` first.
     *
     * @return string[]
     */
    public function typeExpr(Expr $expr): array
    {
        if ($expr instanceof Assign) {
            return $this->typeExpr($expr->expr);
        }
        if ($expr instanceof Clone_) {
            return $this->typeExpr($expr->expr);
        }
        if ($expr instanceof Match_) {
            return array_merge(
                ...array_map(fn(MatchArm $a): array => $this->typeExpr($a->body), $expr->arms),
            );
        }
        if ($expr instanceof New_) {
            if ($expr->class instanceof Class_ || $expr->class instanceof Name) {
                return [IdentifierBuilder::fqClassName($expr->class)];
            }
            return $this->typeExpr($expr->class);
        }
        if (
            ($expr instanceof MethodCall || $expr instanceof NullsafeMethodCall)
            && $expr->name instanceof Identifier
        ) {
            $types = $this->typeExpr($expr->var);
            return $this->types->methodType($types, $expr->name->toString());
        }
        if (
            ($expr instanceof PropertyFetch || $expr instanceof NullsafePropertyFetch)
            && $expr->name instanceof Identifier
        ) {
            $types = $this->typeExpr($expr->var);
            return $this->types->propertyType($types, $expr->name->toString());
        }
        if ($expr instanceof StaticCall && $expr->name instanceof Identifier) {
            $types = $expr->class instanceof Name
                ? [IdentifierBuilder::fqClassName($expr->class)]
                : $this->typeExpr($expr->class);
            return $this->types->methodType($types, $expr->name->toString());
        }
        if ($expr instanceof StaticPropertyFetch && $expr->name instanceof Identifier) {
            $types = $expr->class instanceof Name
                ? [IdentifierBuilder::fqClassName($expr->class)]
                : $this->typeExpr($expr->class);
            return $this->types->propertyType($types, $expr->name->toString());
        }
        if ($expr instanceof Ternary) {
            $elseTypes = $this->typeExpr($expr->else);
            if ($expr->if !== null) {
                return [
                    ...$this->typeExpr($expr->if),
                    ...$elseTypes,
                ];
            }
            return $elseTypes;
        }
        if ($expr instanceof Variable) {
            return $this->types->varType($expr);
        }
        return [];
    }

    /**
     * Collects the expression types from the given definitions.
     *
     * @param  array<string, Definition>  $definitions
     */
    public function collect(array $definitions): void
    {
        // Pass 1: collect types of methods and properties.
        foreach ($definitions as $def) {
            $node = $def->def();
            switch (true) {
                case $node instanceof ClassLike:
                    $this->types->collectUppers($node);
                    break;
                case $node instanceof ClassMethod:
                    $types = NodeTypeUnpacker::unpack($node->returnType);
                    $this->types->add($def, $types);
                    break;
                case $node instanceof PropertyProperty:
                    /** @var Property $node */
                    $node = $node->getAttribute('parent');
                    $types = NodeTypeUnpacker::unpack($node->type);
                    $this->types->add($def, $types);
                    break;
                case $node instanceof Param:
                    $types = NodeTypeUnpacker::unpack($node->type);
                    $this->types->add($def, $types);
                    break;
            }
        }

        // Pass 2: collect variable types.
        foreach ($definitions as $def) {
            $node = $def->def();
            if (!$node instanceof Variable) {
                continue;
            }

            $parent = $node->getAttribute('parent');
            if (!$parent instanceof Assign) {
                continue;
            }
            $types = $this->typeExpr($parent->expr);
            $this->types->add($def, $types);
        }
    }
}
