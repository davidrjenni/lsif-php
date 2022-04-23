<?php

declare(strict_types=1);

namespace LsifPhp\Types\Internal\Ast;

final class CallableType implements Type
{
    public function __construct(private Type $type)
    {
    }

    public function returnType(): Type
    {
        return $this->type;
    }
}
