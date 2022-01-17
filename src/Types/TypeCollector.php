<?php

declare(strict_types=1);

namespace LsifPhp\Types;

use PhpParser\Node\ComplexType;
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
use PhpParser\Node\IntersectionType;
use PhpParser\Node\MatchArm;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\UnionType;

use function array_map;
use function array_merge;
use function count;

/** TypeCollector collects types of expressions which evaluate to named types. */
final class TypeCollector
{

    /** @var array<string, string[]> */
    private array $types;

    public function __construct()
    {
        $this->types = [];
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
                ...array_map(fn (MatchArm $a): array => $this->typeExpr($a->body), $expr->arms)
            );
        }
        if ($expr instanceof New_) {
            if ($expr->class instanceof Class_) {
                return [IdentifierBuilder::fqClassName($expr->class)];
            } elseif ($expr->class instanceof Name) {
                return $this->unpackNamedType($expr->class);
            }
            return $this->typeExpr($expr->class);
        }
        if (
            ($expr instanceof MethodCall || $expr instanceof NullsafeMethodCall)
            && $expr->name instanceof Identifier
        ) {
            $types = $this->typeExpr($expr->var);
            return $this->lookupMethodType($types, $expr->name->toString());
        }
        if (
            ($expr instanceof PropertyFetch || $expr instanceof NullsafePropertyFetch)
            && $expr->name instanceof Identifier
        ) {
            $types = $this->typeExpr($expr->var);
            return $this->lookupClassType($types, $expr->name->toString());
        }
        if ($expr instanceof StaticCall && $expr->name instanceof Identifier) {
            $types = $expr->class instanceof Name
                ? $this->unpackNamedType($expr->class)
                : $this->typeExpr($expr->class);
            return $this->lookupMethodType($types, $expr->name->toString());
        }
        if ($expr instanceof StaticPropertyFetch && $expr->name instanceof Identifier) {
            $types = $expr->class instanceof Name
                ?  $this->unpackNamedType($expr->class)
                : $this->typeExpr($expr->class);
            return $this->lookupClassType($types, $expr->name->toString());
        }
        if ($expr instanceof Ternary) {
            return [
                ...$this->typeExpr($expr->if),
                ...$this->typeExpr($expr->else),
            ];
        }
        if ($expr instanceof Variable) {
            return $this->lookupVariableType($expr);
        }
        return [];
    }

    /**
     * @param  string[]  $classNames
     * @return string[]
     */
    private function lookupMethodType(array $classNames, string $method): array
    {
        return $this->lookupClassType($classNames, "{$method}()");
    }

    /**
     * @param  string[]  $classNames
     * @return string[]
     */
    private function lookupClassType(array $classNames, string $name): array
    {
        $types = [];
        foreach ($classNames as $className) {
            $fqName = "{$className}::{$name}";
            if (isset($this->types[$fqName])) {
                $types = array_merge($types, $this->types[$fqName]);
            }
        }
        return $types;
    }

    /** @return string[] */
    private function lookupVariableType(Variable $var): array
    {
        if ($var->name === 'this') {
            $class = ClassLikeUtil::nearestClassLike($var);
            return $class !== null ? [IdentifierBuilder::fqClassName($class)] : [];
        }
        $fqName = IdentifierBuilder::fqName($var, $var->name);
        return $this->types[$fqName] ?? [];
    }

    /**
     * Collects the property types and return types of methods from the given definitions.
     *
     * @param  Definition[]  $definitions
     */
    public function collect(array $definitions): void
    {
        // Pass 1: collect types of methods and properties.
        foreach ($definitions as $def) {
            $node = $def->def();
            switch (true) {
                case $node instanceof ClassMethod:
                    $types = $this->unpackTypes($node->returnType);
                    $this->addTypes($def, $types);
                    break;
                case $node instanceof PropertyProperty:
                    /** @var Property $node */
                    $node = $node->getAttribute('parent');
                    $types = $this->unpackTypes($node->type);
                    $this->addTypes($def, $types);
                    break;
                case $node instanceof Param:
                    $types = $this->unpackTypes($node->type);
                    $this->addTypes($def, $types);
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
            $this->addTypes($def, $types);
        }
    }

    /** @return string[] */
    private function unpackTypes(Identifier|Name|ComplexType|null ...$types): array
    {
        $typeNames = [];
        foreach ($types as $type) {
            switch (true) {
                case $type instanceof Name:
                    $typeNames = array_merge($typeNames, $this->unpackNamedType($type));
                    break;
                case $type instanceof IntersectionType:
                case $type instanceof UnionType:
                    $typeNames = array_merge($typeNames, $this->unpackTypes(...$type->types));
                    break;
                case $type instanceof NullableType:
                    $typeNames = array_merge($typeNames, $this->unpackTypes($type->type));
                    break;
            }
        }
        return $typeNames;
    }

    /** @return string[] */
    private function unpackNamedType(Name $type): array
    {
        return [IdentifierBuilder::fqClassName($type)];
    }

    /** @param  string[]  $types */
    private function addTypes(Definition $d, array $types): void
    {
        if (count($types) > 0) {
            $this->types[$d->identifier()] = $types;
        }
    }
}
