<?php

declare(strict_types=1);

namespace LsifPhp\Types\Internal\Ast;

final class UniformIterableType implements Type
{
    public function __construct(private Type $type)
    {
    }

    public function valueType(): Type
    {
        return $this->type;
    }
}
