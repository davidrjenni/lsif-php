<?php

declare(strict_types=1);

namespace LsifPhp\Types\Internal;

use LsifPhp\Types\IdentifierBuilder;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\UnionType;

use function array_merge;

/** @internal */
final class NodeTypeUnpacker
{
    /** @return string[] */
    public static function unpack(Identifier|Name|ComplexType|null ...$types): array
    {
        $names = [];
        foreach ($types as $type) {
            switch (true) {
                case $type instanceof Name:
                    $names[] = IdentifierBuilder::fqClassName($type);
                    break;
                case $type instanceof IntersectionType:
                case $type instanceof UnionType:
                    $names = array_merge($names, self::unpack(...$type->types));
                    break;
                case $type instanceof NullableType:
                    $names = array_merge($names, self::unpack($type->type));
                    break;
            }
        }
        return $names;
    }
}
