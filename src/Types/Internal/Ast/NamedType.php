<?php

declare(strict_types=1);

namespace LsifPhp\Types\Internal\Ast;

/** NamedType represents named type. */
final class NamedType implements Type
{
    public function __construct(private string $name)
    {
    }

    public function fqName(): string
    {
        return $this->name;
    }
}
