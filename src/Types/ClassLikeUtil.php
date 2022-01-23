<?php

declare(strict_types=1);

namespace LsifPhp\Types;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;

/** ClassLikeUtil provides utility methods to retrieve class like AST nodes.  */
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
}
