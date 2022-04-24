<?php

declare(strict_types=1);

namespace LsifPhp\Types;

use LsifPhp\Types\Internal\ClassLikeUtil;
use PhpParser\Node;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\VarLikeIdentifier;

use function is_string;
use function ltrim;

/** IdentifierBuilder helps to construct fully qualified names from AST nodes. */
final class IdentifierBuilder
{
    /** @param Name|ClassConst $class the class const node or the class name */
    public static function fqConstName(Name|ClassConst|string $class, string $const): string
    {
        if (is_string($class)) {
            return "{$class}::{$const}";
        }
        if ($class instanceof Name) {
            $fqClassName = self::fqClassName($class);
            return "{$fqClassName}::{$const}";
        }
        return self::fqName($class, $const);
    }

    public static function fqPropertyName(Name|Property|ClassMethod|string $class, VarLikeIdentifier|Identifier|string $property): string
    {
        $property = '$' . ltrim("{$property}", '$');
        if (is_string($class)) {
            return "{$class}::{$property}";
        }
        if ($class instanceof Name) {
            $fqClassName = self::fqClassName($class);
            return "{$fqClassName}::{$property}";
        }
        return self::fqName($class, $property);
    }

    public static function fqMethodName(Name|ClassMethod|string $class, string $method): string
    {
        $method = "{$method}()";
        if (is_string($class)) {
            return "{$class}::{$method}";
        }
        if ($class instanceof Name) {
            $fqClassName = self::fqClassName($class);
            return "{$fqClassName}::{$method}";
        }
        return self::fqName($class, $method);
    }

    public static function fqParamName(Node $node, string $name): string
    {
        return self::fqName($node, $name);
    }

    public static function fqVarName(Node $node, string $name): string
    {
        return self::fqName($node, $name);
    }

    /** Returns the fully qualified name of the given node and initial name. */
    private static function fqName(Node $node, string $name): string
    {
        /** @var Node|null $node */
        $node = $node->getAttribute('parent');
        if ($node === null) {
            return $name;
        }

        if ($node instanceof FunctionLike) {
            $functionLikeName = self::functionLikeName($node);
            $name = "{$functionLikeName}::{$name}";
        } elseif ($node instanceof ClassLike) {
            $classLikeName = self::classLikeName($node);
            $name = "{$classLikeName}::{$name}";
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
                    $classLike = ClassLikeUtil::nearestClassLike($node);
                    if (isset($classLike->extends)) {
                        return self::fqClassName($classLike->extends);
                    }
                    // fallthrough
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
