<?php

declare(strict_types=1);

namespace LsifPhp\Types;

use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike;

/** IdentifierBuilder helps to construct fully qualified names from AST nodes. */
final class IdentifierBuilder
{

    /** Returns the fully qualified name of the given node and initial name. */
    public static function fqName(Node $node, string $name): string
    {
        /** @var Node|null $node */
        $node = $node->getAttribute('parent');
        if ($node === null) {
            return $name;
        }

        if ($node instanceof FunctionLike) {
            $functionLikeName = self::functionLikeName($node);
            $name = "{$functionLikeName}::$name";
        } elseif ($node instanceof ClassLike) {
            $classLikeName = self::classLikeName($node);
            $name = "{$classLikeName}::$name";
        }
        return self::fqName($node, $name);
    }

    /** Returns the fully qualified name of the given class like AST node. */
    public static function fqClassName(ClassLike|Name $node): string
    {
        if ($node instanceof Name) {
            $name = $node->toString();
            switch ($name) {
                case 'parent':
                    return self::fqClassName(ClassLikeUtil::nearestClassLike($node)->extends);
                case 'self':
                case 'static':
                    $node = ClassLikeUtil::nearestClassLike($node);
                    break;
                default:
                    return $name;
            }
        }

        $name = self::classLikeName($node);
        $parent = $node->getAttribute('parent');
        return $parent !== null
            ? self::fqName($parent, $name)
            : $name;
    }

    private static function classLikeName(ClassLike $node): string
    {
        return !isset($node->namespacedName)
            ? "anon-class-{$node->getStartTokenPos()}"
            : $node->namespacedName->toString();
    }

    private static function functionLikeName(FunctionLike $node): string
    {
        return !isset($node->name)
            ? "anon-func-{$node->getStartTokenPos()}()"
            : "{$node->name}()";
    }
}
