<?php

declare(strict_types=1);

namespace LsifPhp\Types\Internal;

use LsifPhp\Parser\DocCommentParser;
use LsifPhp\Types\Definition;
use LsifPhp\Types\IdentifierBuilder;
use LsifPhp\Types\Internal\Ast\CompositeType;
use LsifPhp\Types\Internal\Ast\Parser;
use LsifPhp\Types\Internal\Ast\Type;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Clone_;
use PhpParser\Node\Expr\Match_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\NullsafeMethodCall;
use PhpParser\Node\Expr\NullsafePropertyFetch;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\PropertyProperty;

/**
 * TypeCollector collects types of expressions which evaluate to named types.
 * @internal
 */
final class TypeCollector
{
    private TypeMap $types;

    private DocCommentParser $docCommentParser;

    public function __construct()
    {
        $this->types = new TypeMap();
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
        return $this->types->uppers($classes);
    }

    /**
     * Returns the fully qualified names of the types to which the expression
     * can evaluate to, or an empty array for unknown or unnamed types.
     * NOTE: call `collect` first.
     */
    public function typeExpr(Expr $expr): ?Type
    {
        if ($expr instanceof Assign) {
            return $this->typeExpr($expr->expr);
        }
        if ($expr instanceof Clone_) {
            return $this->typeExpr($expr->expr);
        }
        if ($expr instanceof Match_) {
            $types = [];
            foreach ($expr->arms as $a) {
                $types[] = $this->typeExpr($a->body);
            }
            return CompositeType::fromTypes($types);
        }
        if ($expr instanceof New_) {
            if ($expr->class instanceof Class_ || $expr->class instanceof Name) {
                return Parser::fromNode($expr->class);
            }
            return $this->typeExpr($expr->class);
        }
        if (
            ($expr instanceof MethodCall || $expr instanceof NullsafeMethodCall)
            && $expr->name instanceof Identifier
        ) {
            $types = Parser::flatten($this->typeExpr($expr->var));
            return $this->types->methodType($types, $expr->name->toString());
        }
        if (
            ($expr instanceof PropertyFetch || $expr instanceof NullsafePropertyFetch)
            && $expr->name instanceof Identifier
        ) {
            $types = Parser::flatten($this->typeExpr($expr->var));
            return $this->types->propertyType($types, $expr->name->toString());
        }
        if ($expr instanceof StaticCall && $expr->name instanceof Identifier) {
            $types = $expr->class instanceof Name
                ? [IdentifierBuilder::fqClassName($expr->class)]
                : Parser::flatten($this->typeExpr($expr->class));
            return $this->types->methodType($types, $expr->name->toString());
        }
        if ($expr instanceof StaticPropertyFetch && $expr->name instanceof Identifier) {
            $types = $expr->class instanceof Name
                ? [IdentifierBuilder::fqClassName($expr->class)]
                : Parser::flatten($this->typeExpr($expr->class));
            return $this->types->propertyType($types, $expr->name->toString());
        }
        if ($expr instanceof Ternary) {
            $elseType = $this->typeExpr($expr->else);
            if ($expr->if !== null) {
                $ifType = $this->typeExpr($expr->if);
                return CompositeType::fromTypes([$ifType, $elseType]);
            }
            return $elseType;
        }
        if ($expr instanceof Variable) {
            return $this->types->varType($expr);
        }
        return null;
    }

    /**
     * Collects the expression types from the given definitions.
     *
     * @param  array<string, Definition>  $definitions
     */
    public function collect(array $definitions): void
    {
        // Pass 1: collect types of methods and properties.
        foreach ($definitions as $def) {
            $node = $def->def();
            switch (true) {
                case $node instanceof ClassLike:
                    $this->types->collectUppers($node);
                    break;
                case $node instanceof ClassMethod:
                    $type = Parser::fromNode($node->returnType);
                    if ($type === null) {
                        $returnType = $this->docCommentParser->parseReturnType($node);
                        if ($returnType === null) {
                            break;
                        }
                        $type = Parser::fromDocType($node, $returnType);
                    }
                    $this->types->add($def, $type);
                    break;
                case $node instanceof PropertyProperty:
                    /** @var Property $node */
                    $node = $node->getAttribute('parent');
                    $type = Parser::fromNode($node->type);
                    if ($type === null) {
                        $propType = $this->docCommentParser->parsePropertyType($node);
                        if ($propType === null) {
                            break;
                        }
                        $type = Parser::fromDocType($node, $propType);
                    }
                    $this->types->add($def, $type);
                    break;
                case $node instanceof Param:
                    $type = Parser::fromNode($node->type);
                    $this->types->add($def, $type);
                    break;
            }
        }

        // Pass 2: collect variable types.
        foreach ($definitions as $def) {
            $node = $def->def();
            if (!$node instanceof Variable) {
                continue;
            }

            $parent = $node->getAttribute('parent');
            if (!$parent instanceof Assign) {
                continue;
            }
            $types = $this->typeExpr($parent->expr);
            $this->types->add($def, $types);
        }
    }
}
