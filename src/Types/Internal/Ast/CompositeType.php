<?php

declare(strict_types=1);

namespace LsifPhp\Types\Internal\Ast;

use function count;

/** CompositeType holds the types of an intersection or union type. */
final class CompositeType implements Type
{
    /** @param Type[] $types */
    private function __construct(private array $types)
    {
    }

    /** @return Type[] */
    public function types(): array
    {
        return $this->types;
    }

    /** @param array<Type|null> $types */
    public static function fromTypes(array $types): ?Type
    {
        $ts = [];
        foreach ($types as $t) {
            if ($t !== null) {
                $ts[] = $t;
            }
        }
        switch (count($ts)) {
            case 0:
                return null;
            case 1:
                return $ts[0];
            default:
                return new CompositeType($ts);
        }
    }
}
