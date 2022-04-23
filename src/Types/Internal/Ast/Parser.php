<?php

declare(strict_types=1);

namespace LsifPhp\Types\Internal\Ast;

use LogicException;
use LsifPhp\Types\IdentifierBuilder;
use LsifPhp\Types\Internal\ClassLikeUtil;
use PhpParser\ErrorHandler\Throwing as ThrowingErrorHandler;
use PhpParser\NameContext;
use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\UnionType;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\CallableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\NullableTypeNode;
use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;

use function array_merge;
use function ltrim;
use function str_starts_with;

final class Parser
{
    public static function fromNode(Identifier|Class_|ClassLike|Name|ComplexType|null $type): ?Type
    {
        switch (true) {
            case $type instanceof Class_:
            case $type instanceof ClassLike:
            case $type instanceof Name:
                $name = IdentifierBuilder::fqClassName($type);
                return new NamedType($name);
            case $type instanceof IntersectionType:
            case $type instanceof UnionType:
                $types = [];
                foreach ($type->types as $t) {
                    $types[] = self::fromNode($t);
                }
                return CompositeType::fromTypes($types);
            case $type instanceof NullableType:
                return self::fromNode($type->type);
            default:
                return null;
        }
    }

    public static function fromDocType(Node $node, TypeNode $type): ?Type
    {
        switch (true) {
            case $type instanceof ArrayShapeNode:
                $types = [];
                foreach ($type->items as $i) {
                    $types[] = self::fromDocType($node, $i->valueType);
                }
                return new MixedIterableType($types);
            case $type instanceof ArrayTypeNode:
                $t = self::fromDocType($node, $type->type);
                if ($t === null) {
                    return null;
                }
                return new UniformIterableType($t);
            case $type instanceof CallableTypeNode:
                $t = self::fromDocType($node, $type->returnType);
                if ($t === null) {
                    return null;
                }
                return new CallableType($t);
            case $type instanceof ConditionalTypeNode:
                $ifType = self::fromDocType($node, $type->if);
                $elseType = self::fromDocType($node, $type->else);
                return CompositeType::fromTypes([$ifType, $elseType]);
            case $type instanceof ConstTypeNode:
                return null;
            case $type instanceof GenericTypeNode:
                return self::fromDocType($node, $type->type);
            case $type instanceof IdentifierTypeNode:
                $name = str_starts_with($type->name, '\\')
                    ? new FullyQualified(ltrim($type->name, '\\'))
                    : new Name($type->name);
                if ($name->isSpecialClassName()) {
                    $fqName = IdentifierBuilder::fqClassName($name);
                    return new NamedType($fqName);
                }
                $namespace = ClassLikeUtil::nearestNamespace($node);
                if ($namespace === null) {
                    return null;
                }
                $nameCtx = self::prepareNameContext($namespace);
                $fqName = $nameCtx->getResolvedName($name, Use_::TYPE_NORMAL);
                if ($fqName === null) {
                    return null;
                }
                return new NamedType($fqName->toString());
            case $type instanceof IntersectionTypeNode:
            case $type instanceof UnionTypeNode:
                $types = [];
                foreach ($type->types as $t) {
                    $types[] = self::fromDocType($node, $t);
                }
                return CompositeType::fromTypes($types);
            case $type instanceof NullableTypeNode:
                return self::fromDocType($node, $type->type);
            case $type instanceof ThisTypeNode:
                $classLike = $node instanceof ClassLike ? $node : ClassLikeUtil::nearestClassLike($node);
                if ($classLike === null) {
                    return null;
                }
                $fqName = IdentifierBuilder::fqClassName($classLike);
                return new NamedType($fqName);
            default:
                return null;
        }
    }

    private static function prepareNameContext(Namespace_ $namespace): NameContext
    {
        $nameCtx = new NameContext(new ThrowingErrorHandler());
        $nameCtx->startNamespace($namespace->name);
        foreach ($namespace->stmts as $stmt) {
            if ($stmt instanceof Use_) {
                foreach ($stmt->uses as $use) {
                    $type = $stmt->type | $use->type;
                    $nameCtx->addAlias($use->name, (string)$use->getAlias(), $type, $use->getAttributes());
                }
            } elseif ($stmt instanceof GroupUse) {
                foreach ($stmt->uses as $use) {
                    $name = Name::concat($stmt->prefix, $use->name);
                    if ($name === null) {
                        continue;
                    }
                    $type = $stmt->type | $use->type;
                    $nameCtx->addAlias($name, (string)$use->getAlias(), $type, $use->getAttributes());
                }
            }
        }
        return $nameCtx;
    }

    /** @return string[] */
    public static function flatten(?Type $type): array
    {
        switch (true) {
            case $type instanceof CallableType:
                return self::flatten($type->returnType());
            case $type instanceof CompositeType:
                $names = [];
                foreach ($type->types() as $t) {
                    $names = array_merge($names, self::flatten($t));
                }
                return $names;
            case $type instanceof NamedType:
                return [$type->fqName()];
            case $type instanceof MixedIterableType:
            case $type instanceof UniformIterableType:
                return [];
            case $type === null:
                return [];
            default:
                throw new LogicException('Unknown type: ' . $type::class);
        }
    }
}
