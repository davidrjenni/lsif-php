<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

final class Contains extends Edge
{
    /** @param  int[]  $inVs */
    public function __construct(int $id, private int $outV, private array $inVs)
    {
        parent::__construct($id, Edge::LABEL_CONTAINS);
    }

    /** @return array<string, int|string|int[]> */
    public function jsonSerialize(): array
    {
        return parent::jsonSerialize() + [
            'outV' => $this->outV,
            'inVs' => $this->inVs,
        ];
    }
}
