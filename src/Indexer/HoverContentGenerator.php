<?php

declare(strict_types=1);

namespace LsifPhp\Indexer;

use LogicException;
use LsifPhp\Protocol\HoverResultContent;
use LsifPhp\Protocol\MarkedString;
use LsifPhp\Protocol\MarkupContent;
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
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\UnionType;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;

use function array_map;
use function count;
use function implode;
use function preg_replace;
use function str_replace;

/** HoverContentGenerator provides methods to create hover content for definitions. */
final class HoverContentGenerator
{
    private PrettyPrinter $printer;

    public function __construct()
    {
        $this->printer = new PrettyPrinter();
    }

    /** @return HoverResultContent[] */
    public function create(Definition $def, string $languageId): array
    {
        $info = $this->createInfo($def->def());
        $hoverContent = [new MarkedString($languageId, $info)];

        $comment = $def->doc();
        if ($comment !== null) {
            $hoverContent[] = new MarkupContent(
                MarkupContent::KIND_MARKDOWN,
                $this->cleanup($comment->getText()),
            );
        }

        return $hoverContent;
    }

    private function createInfo(Node $node): string
    {
        if ($node instanceof Class_) {
            return $this->classInfo($node);
        }
        if ($node instanceof Interface_) {
            return $this->interfaceInfo($node);
        }
        if ($node instanceof Trait_) {
            return "trait {$node->name}";
        }
        if ($node instanceof Enum_) {
            return "enum {$node->name}";
        }
        if ($node instanceof Const_) {
            return $this->constInfo($node);
        }
        if ($node instanceof PropertyProperty) {
            return $this->propertyInfo($node);
        }
        if ($node instanceof ClassMethod) {
            return $this->methodInfo($node);
        }
        if ($node instanceof Param) {
            return $this->printer->prettyPrint([$node]);
        }
        if ($node instanceof Variable) {
            return $this->printer->prettyPrint([$node]);
        }

        throw new LogicException('Unexpected node type: ' . $node::class);
    }

    private function classInfo(Class_ $class): string
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
            ? "{$info} implements " . $this->joinNames($class->implements)
            : $info;
    }

    private function interfaceInfo(Interface_ $interface): string
    {
        $info = "interface {$interface->name}";
        return count($interface->extends) > 0
            ? "{$info} implements " . $this->joinNames($interface->extends)
            : $info;
    }

    private function constInfo(Const_ $const): string
    {
        $info = "const {$const->name} = " . $this->printer->prettyPrint([$const->value]);
        $classConst = $const->getAttribute('parent');
        if (!$classConst instanceof ClassConst) {
            return $info;
        }
        if ($classConst->isFinal()) {
            $info = "final {$info}";
        }
        return $this->visibility($classConst) . " $info";
    }

    private function propertyInfo(PropertyProperty $property): string
    {
        /** @var Property $classProperty */
        $classProperty = $property->getAttribute('parent');
        $modifiers = $this->visibility($classProperty);
        if ($classProperty->isStatic()) {
            $modifiers = "{$modifiers} static";
        }
        if ($classProperty->isReadonly()) {
            $modifiers = "{$modifiers} readonly";
        }

        $info = $classProperty->type !== null
            ? "{$modifiers} " . $this->typeInfo($classProperty->type) . " \${$property->name}"
            : "{$modifiers} \${$property->name}";

        return $property->default !== null
            ? "{$info} = " . $this->printer->prettyPrint([$property->default])
            : $info;
    }

    private function methodInfo(ClassMethod $method): string
    {
        $modifiers = $this->visibility($method);
        if ($method->isStatic()) {
            $modifiers = "{$modifiers} static";
        }
        if ($method->isFinal()) {
            $modifiers = "{$modifiers} final";
        } elseif ($method->isAbstract()) {
            $modifiers = "{$modifiers} abstract";
        }

        $info = "{$modifiers} function {$method->name}(";
        foreach ($method->params as $i => $param) {
            if ($i > 0) {
                $info .= ', ';
            }
            $info .= $this->printer->prettyPrint([$param]);
        }

        return $method->returnType !== null
            ? "{$info}): " . $this->typeInfo($method->returnType)
            : "{$info})";
    }

    private function typeInfo(Identifier|Name|ComplexType|null $type): string
    {
        if ($type === null) {
            return '';
        }
        if ($type instanceof NullableType) {
            return "?{$type->type}";
        }
        if ($type instanceof UnionType) {
            return implode('|', array_map(fn($t): string => $this->typeInfo($t), $type->types));
        }
        if ($type instanceof IntersectionType) {
            return implode('&', array_map(fn($t): string => $this->typeInfo($t), $type->types));
        }
        if ($type instanceof Identifier || $type instanceof Name) {
            return $type->toString();
        }
        throw new LogicException('Unexpected node type: ' . $type::class);
    }

    private function visibility(ClassConst|Property|ClassMethod $node): string
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

    /** @param Name[] $names */
    private function joinNames(array $names): string
    {
        return implode(
            ', ',
            array_map(fn(Name $name): string => $name->toString(), $names),
        );
    }

    private function cleanup(string $comment): string
    {
        $comment = $this->remove('(^(\s+)?/\*\*\s)m', $comment);
        $comment = $this->remove('(^(\s+)?\*\s)m', $comment, -1);
        $comment = $this->remove('(^(\s+)?\*/)m', $comment);
        $comment = $this->remove('((\s+)?\*/$)m', $comment);
        return str_replace("\n", '<br>', $comment);
    }

    private function remove(string $pattern, string $subject, int $limit = 1): string
    {
        return preg_replace($pattern, '', $subject, $limit) ?? $subject;
    }
}
