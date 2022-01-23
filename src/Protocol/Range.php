<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

final class Range extends Vertex
{
    private RangeData $rangeData;

    public function __construct(
        int $id,
        Pos $start,
        Pos $end,
        private ?RangeTag $tag = null
    ) {
        parent::__construct($id, Vertex::LABEL_RANGE);

        $this->rangeData = new RangeData($start, $end);
    }

    /** @return array<string, int|string|Pos|RangeTag> */
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize() + $this->rangeData->jsonSerialize();

        return $this->tag !== null
            ? $data + ['tag' => $this->tag]
            : $data;
    }
}
