<?php

declare(strict_types=1);

namespace LsifPhp\Types\Internal\Ast;

final class MixedIterableType implements Type
{
    /** @var array<Type|null> */
    private array $types;

    /** @param array<Type|null> $types */
    public function __construct(array $types)
    {
        $this->types = [];
        foreach ($types as $i => $t) {
            if ($t !== null) {
                $this->types[$i] = $t;
            }
        }
    }

    public function valueType(int|string $key): ?Type
    {
        return $this->types[$key] ?? null;
    }
}
