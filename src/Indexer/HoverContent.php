<?php

declare(strict_types=1);

namespace LsifPhp\Indexer;

use LsifPhp\Protocol\HoverResultContent;
use LsifPhp\Protocol\MarkedString;
use LsifPhp\Protocol\MarkupContent;
use LogicException;
use LsifPhp\Types\Definition;
use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\UnionType;

use function array_map;
use function count;
use function implode;
use function preg_replace;
use function str_replace;

/** HoverContent provides methods to create hover content for definitions. */
final class HoverContent
{
    /** @return HoverResultContent[] */
    public static function create(Definition $def, string $languageId): array
    {
        $info = self::createInfo($def->def());
        $hoverContent = [new MarkedString($languageId, $info)];

        $comment = $def->doc();
        if ($comment !== null) {
            $hoverContent[] = new MarkupContent(
                MarkupContent::KIND_MARKDOWN,
                self::cleanup($comment->getText())
            );
        }

        return $hoverContent;
    }

    private static function createInfo(Node $node): string
    {
        if ($node instanceof Class_) {
            return self::classInfo($node);
        }
        if ($node instanceof Interface_) {
            return self::interfaceInfo($node);
        }
        if ($node instanceof Trait_) {
            return "trait {$node->name}";
        }
        if ($node instanceof Const_) {
            return self::constInfo($node);
        }
        if ($node instanceof PropertyProperty) {
            return self::propertyInfo($node);
        }
        if ($node instanceof ClassMethod) {
            return self::methodInfo($node);
        }
        if ($node instanceof Param) {
            return self::paramInfo($node);
        }
        if ($node instanceof Variable) {
            return self::varInfo($node);
        }

        throw new LogicException('Unexpected node type: ' . $node::class);
    }

    private static function classInfo(Class_ $class): string
    {
        $info = "class {$class->name}";

        if ($class->isAbstract()) {
            $info = "abstract {$info}";
        } elseif ($class->isFinal()) {
            $info = "final {$info}";
        }
        if ($class->extends !== null) {
            $info .= " extends {$class->extends}";
        }

        return count($class->implements) > 0
            ? "{$info} implements " . self::joinNames($class->implements)
            : $info;
    }

    private static function interfaceInfo(Interface_ $interface): string
    {
        $info = "interface {$interface->name}";
        return count($interface->extends) > 0
            ? "{$info} implements " . self::joinNames($interface->extends)
            : $info;
    }

    private static function constInfo(Const_ $const): string
    {
        $info = "const {$const->name}";
        $classConst = $const->getAttribute('parent');
        if (!$classConst instanceof ClassConst) {
            return $info;
        }
        if ($classConst->isFinal()) {
            $info = "final $info";
        }
        return self::visibility($classConst) . " $info";
    }

    private static function propertyInfo(PropertyProperty $property): string
    {
        /** @var Property $classProperty */
        $classProperty = $property->getAttribute('parent');
        $modifiers = self::visibility($classProperty);
        if ($classProperty->isStatic()) {
            $modifiers = "{$modifiers} static";
        }

        return $classProperty->type !== null
            ? "{$modifiers} " . self::typeInfo($classProperty->type) . " \${$property->name}"
            : "$modifiers \${$property->name}";
    }

    private static function methodInfo(ClassMethod $method): string
    {
        $modifiers = self::visibility($method);
        if ($method->isStatic()) {
            $modifiers = "$modifiers static";
        }
        if ($method->isFinal()) {
            $modifiers = "$modifiers final";
        } elseif ($method->isAbstract()) {
            $modifiers = "$modifiers abstract";
        }

        $info = "{$modifiers} function {$method->name}(";
        foreach ($method->params as $i => $param) {
            if ($i > 0) {
                $info .= ', ';
            }
            $info .= self::paramInfo($param);
        }

        return $method->returnType !== null
            ? "{$info}): " . self::typeInfo($method->returnType)
            : "{$info})";
    }

    private static function paramInfo(Param $param): string
    {
        $info = '';
        if ($param->flags !== 0) {
            if (($param->flags & Class_::MODIFIER_PUBLIC) !== 0) {
                $info = 'public ';
            } elseif (($param->flags & Class_::MODIFIER_PROTECTED) !== 0) {
                $info = 'protected ';
            } elseif (($param->flags & Class_::MODIFIER_PRIVATE) !== 0) {
                $info = 'private ';
            }
            if (($param->flags & Class_::MODIFIER_READONLY) !== 0) {
                $info .= ' readonly ';
            }
        }
        if ($param->type !== null) {
            $info .= self::typeInfo($param->type) . ' ';
        }

        $paramName = "\${$param->var->name}";
        if ($param->byRef) {
            $paramName = "&{$paramName}";
        }
        if ($param->variadic) {
            $paramName = "...{$paramName}";
        }

        return "{$info}{$paramName}";
    }

    private static function varInfo(Variable $var): string
    {
        return "\${$var->name}";
    }

    private static function typeInfo(Identifier|Name|ComplexType|null $type): string
    {
        if ($type === null) {
            return '';
        }
        if ($type instanceof NullableType) {
            return "?{$type->type}";
        }
        if ($type instanceof UnionType) {
            return implode('|', array_map(fn ($t): string => self::typeInfo($t), $type->types));
        }
        if ($type instanceof IntersectionType) {
            return implode('&', array_map(fn ($t): string => self::typeInfo($t), $type->types));
        }
        return $type->toString();
    }

    private static function visibility(ClassConst|Property|ClassMethod $node): string
    {
        if ($node->isPrivate()) {
            return 'private';
        }
        if ($node->isProtected()) {
            return 'protected';
        }
        if ($node->isPublic()) {
            return 'public';
        }
        return '';
    }

    /** @param  Name[]  $names */
    private static function joinNames(array $names): string
    {
        return implode(
            ', ',
            array_map(fn (Name $name): string => $name->toString(), $names)
        );
    }

    private static function cleanup(string $comment): string
    {
        $comment = preg_replace('(^(\s+)?/\*\*\s)m', '', $comment, 1);
        $comment = preg_replace('(^(\s+)?\*\s)m', '', $comment, -1);
        $comment = preg_replace('(^(\s+)?\*/)m', '', $comment, 1);
        $comment = preg_replace('((\s+)?\*/$)m', '', $comment, 1);
        return str_replace("\n", '<br>', $comment);
    }
}
