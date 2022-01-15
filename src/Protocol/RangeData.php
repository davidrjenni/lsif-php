<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

use JsonSerializable;

final class RangeData implements JsonSerializable
{

    public function __construct(private Pos $start, private Pos $end)
    {
    }

    /** @return array<string, Pos> */
    public function jsonSerialize(): array
    {
        return [
            'start' => $this->start,
            'end'   => $this->end,
        ];
    }
}
