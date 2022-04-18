<?php

declare(strict_types=1);

namespace LsifPhp\Types;

use LsifPhp\Types\Internal\DefinitionCollector;
use LsifPhp\Types\Internal\TypeCollector;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt;

use function array_merge;

final class TypeInfo
{
    private DefinitionCollector $definitionCollector;

    private TypeCollector $typeCollector;

    public function __construct()
    {
        $this->definitionCollector = new DefinitionCollector();
        $this->typeCollector = new TypeCollector();
    }

    /**
     * Collects all definitions from the given statements.
     *
     * @param  Stmt[]  $stmts
     */
    public function collectDefinitions(int $docId, array $stmts): void
    {
        $this->definitionCollector->collect($docId, $stmts);
    }

    /**
     * Collects the expression types from the given definitions.
     *
     * NOTE: Call `collectDefinitions` for all files first.
     */
    public function collectTypes(): void
    {
        $defs = $this->definitionCollector->definitions();
        $this->typeCollector->collect($defs);
    }

    /**
     * Returns all collected definitions.
     * NOTE: Call `collect` first, otherwise the result is empty.
     *
     * @return array<string, Definition>
     */
    public function definitions(): array
    {
        return $this->definitionCollector->definitions();
    }

    /** Returns the fully-qualified name with which the constant is identified or an empty string if not found. */
    public function findConstant(Name $class, string $name): string
    {
        return $this->find($class, fn(string $class): string => IdentifierBuilder::fqConstName($class, $name));
    }

    /** Returns the fully-qualified name with which the method is identified or an empty string if not found. */
    public function findMethod(Name|Expr $expr, string $name): string
    {
        return $this->find($expr, fn(string $class): string => IdentifierBuilder::fqMethodName($class, $name));
    }

    /** Returns the fully-qualified name with which the property is identified or an empty string if not found. */
    public function findProperty(Name|Expr $expr, string $name): string
    {
        return $this->find($expr, fn(string $class): string => IdentifierBuilder::fqPropertyName($class, $name));
    }

    /** @param  callable(string): string  $name */
    private function find(Name|Expr $expr, callable $name): string
    {
        $classes = $expr instanceof Name
            ? [IdentifierBuilder::fqClassName($expr)]
            : $this->typeCollector->typeExpr($expr);
        $uppers = $this->typeCollector->uppers($classes);
        $candidates = array_merge($classes, $uppers);
        foreach ($candidates as $class) {
            $fqName = $name($class);
            if ($this->definitionCollector->defined($fqName)) {
                return $fqName;
            }
        }
        return '';
    }
}
