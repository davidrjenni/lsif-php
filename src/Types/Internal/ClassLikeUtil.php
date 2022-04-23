<?php

declare(strict_types=1);

namespace LsifPhp\Types\Internal;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Namespace_;

/**
 * ClassLikeUtil provides utility methods to retrieve class like AST nodes.
 * @internal
 */
final class ClassLikeUtil
{
    /** Returns the nearest class like parent node. */
    public static function nearestClassLike(Node $node): ?ClassLike
    {
        while (true) {
            $node = $node->getAttribute('parent');
            if ($node === null) {
                return null;
            }
            if ($node instanceof ClassLike) {
                return $node;
            }
        }
    }

    /** Returns the nearest namespace parent node. */
    public static function nearestNamespace(Node $node): ?Namespace_
    {
        while (true) {
            $node = $node->getAttribute('parent');
            if ($node === null) {
                return null;
            }
            if ($node instanceof Namespace_) {
                return $node;
            }
        }
    }
}
