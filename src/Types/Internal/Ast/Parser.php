<?php

declare(strict_types=1);

namespace LsifPhp\Types\Internal\Ast;

use LogicException;
use LsifPhp\Types\IdentifierBuilder;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\UnionType;

use function array_merge;

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

    /** @return string[] */
    public static function flatten(?Type $type): array
    {
        switch (true) {
            case $type instanceof CompositeType:
                $names = [];
                foreach ($type->types() as $t) {
                    $names = array_merge($names, self::flatten($t));
                }
                return $names;
            case $type instanceof NamedType:
                return [$type->fqName()];
            case $type === null:
                return [];
            default:
                throw new LogicException('Unknown type: ' . $type::class);
        }
    }
}
