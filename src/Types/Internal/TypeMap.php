<?php

declare(strict_types=1);

namespace LsifPhp\Types\Internal;

use LsifPhp\Types\Definition;
use LsifPhp\Types\IdentifierBuilder;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Interface_;

use function array_merge;
use function count;
use function is_string;

/** @internal */
final class TypeMap
{
    /** @var array<string, string[]> */
    public array $types;

    /** @var array<string, string[]> */
    private array $uppers;

    public function __construct()
    {
        $this->types = [];
        $this->uppers = [];
    }

    /** @param  string[]  $types */
    public function add(Definition $d, array $types): void
    {
        if (count($types) > 0) {
            $this->types[$d->identifier()] = $types;
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

    /**
     * @param  string[]  $classNames
     * @return string[]
     */
    public function classType(array $classNames, string $name): array
    {
        $types = [];
        foreach ($classNames as $className) {
            $fqName = "{$className}::{$name}";
            if (isset($this->types[$fqName])) {
                $types = array_merge($types, $this->types[$fqName]);
            }
        }
        if (count($types) > 0) {
            return $types;
        }
        foreach ($classNames as $class) {
            $uppers = $this->uppers[$class] ?? [];
            $types = $this->classType($uppers, $name);
            if (count($types) > 0) {
                return $types;
            }
        }
        return $types;
    }

    /**
     * @param  string[]  $classNames
     * @return string[]
     */
    public function methodType(array $classNames, string $method): array
    {
        $types = $this->classType($classNames, "{$method}()");
        if (count($types) > 0) {
            return $types;
        }
        foreach ($classNames as $name) {
            $uppers = $this->uppers[$name] ?? [];
            $types = $this->methodType($uppers, $method);
            if (count($types) > 0) {
                return $types;
            }
        }
        return [];
    }

    /** @return string[] */
    public function varType(Variable $var): array
    {
        if ($var->name === 'this') {
            $class = ClassLikeUtil::nearestClassLike($var);
            return $class !== null ? [IdentifierBuilder::fqClassName($class)] : [];
        }
        if (!is_string($var->name)) {
            return [];
        }
        $fqName = IdentifierBuilder::fqName($var, $var->name);
        return $this->types[$fqName] ?? [];
    }
}
