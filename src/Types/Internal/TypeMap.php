<?php

declare(strict_types=1);

namespace LsifPhp\Types\Internal;

use LsifPhp\Parser\DocCommentParser;
use LsifPhp\Types\Definition;
use LsifPhp\Types\IdentifierBuilder;
use LsifPhp\Types\Internal\Ast\Parser;
use LsifPhp\Types\Internal\Ast\Type;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Interface_;

use function array_merge;
use function count;
use function in_array;
use function is_string;

/** @internal */
final class TypeMap
{
    /** @var array<string, Type> */
    public array $types;

    /** @var array<string, string[]> */
    private array $uppers;

    private DocCommentParser $docCommentParser;

    public function __construct()
    {
        $this->types = [];
        $this->uppers = [];
        $this->docCommentParser = new DocCommentParser();
    }

    /**
     * Returns all fully-qualified names of "upper" class-likes, e.g. parent classes, interfaces or traits.
     *
     * @param  string[]  $classes
     * @return string[]
     */
    public function uppers(array $classes): array
    {
        $uppers = [];
        foreach ($classes as $class) {
            if (isset($this->uppers[$class])) {
                $uppers = array_merge($uppers, $this->uppers[$class]);
            }
        }
        if (count($uppers) > 0) {
            $upperUppers = $this->uppers($uppers);
            return array_merge($uppers, $upperUppers);
        }
        return $uppers;
    }

    public function add(Definition $d, ?Type $type): void
    {
        if ($type !== null) {
            $this->types[$d->identifier()] = $type;
        }
    }

    public function collectUppers(ClassLike $classLike): void
    {
        if ($classLike instanceof Class_) {
            foreach ($classLike->implements as $iface) {
                $this->addClassLikeUpper($classLike, $iface);
            }
            if ($classLike->extends !== null) {
                $this->addClassLikeUpper($classLike, $classLike->extends);
            }
        }

        if ($classLike instanceof Interface_) {
            foreach ($classLike->extends as $iface) {
                $this->addClassLikeUpper($classLike, $iface);
            }
        }

        foreach ($classLike->getTraitUses() as $traitUse) {
            foreach ($traitUse->traits as $trait) {
                $this->addClassLikeUpper($classLike, $trait);
            }
        }

        $this->addMixins($classLike);
    }

    private function addClassLikeUpper(ClassLike $classLike, Name $upper): void
    {
        $fqName = IdentifierBuilder::fqClassName($classLike);
        if (!isset($this->uppers[$fqName])) {
            $this->uppers[$fqName] = [];
        }
        $name = IdentifierBuilder::fqClassName($upper);
        $this->uppers[$fqName][] = $name;
    }

    private function addMixins(ClassLike $classLike): void
    {
        $mixins = $this->docCommentParser->parseMixins($classLike);
        foreach ($mixins as $mixin) {
            $type = Parser::fromDocType($classLike, $mixin);
            $fqName = IdentifierBuilder::fqClassName($classLike);
            $classNames = Parser::flatten($type);
            foreach ($classNames as $class) {
                if (!isset($this->uppers[$fqName])) {
                    $this->uppers[$fqName] = [];
                }
                if ($fqName !== $class && !in_array($fqName, $this->uppers[$class] ?? [], true)) {
                    $this->uppers[$fqName][] = $class;
                }
            }
        }
    }

    /** @param  string[]  $classNames */
    public function propertyType(array $classNames, string $name): ?Type
    {
        return $this->classType($classNames, '$' . $name);
    }

    /** @param  string[]  $classNames */
    public function methodType(array $classNames, string $method): ?Type
    {
        $type = $this->classType($classNames, "{$method}()");
        if ($type !== null) {
            return $type;
        }
        foreach ($classNames as $name) {
            $uppers = $this->uppers[$name] ?? [];
            $type = $this->methodType($uppers, $method);
            if ($type !== null) {
                return $type;
            }
        }
        return null;
    }

    /** @param  string[]  $classNames */
    private function classType(array $classNames, string $name): ?Type
    {
        foreach ($classNames as $className) {
            $fqName = "{$className}::{$name}";
            if (isset($this->types[$fqName])) {
                return $this->types[$fqName];
            }
        }
        foreach ($classNames as $class) {
            $uppers = $this->uppers[$class] ?? [];
            $type = $this->classType($uppers, $name);
            if ($type !== null) {
                return $type;
            }
        }
        return null;
    }

    public function varType(Variable $var): ?Type
    {
        if ($var->name === 'this') {
            $class = ClassLikeUtil::nearestClassLike($var);
            return $class !== null ? Parser::fromNode($class) : null;
        }
        if (!is_string($var->name)) {
            return null;
        }
        $fqName = IdentifierBuilder::fqVarName($var, $var->name);
        return $this->types[$fqName] ?? null;
    }
}
