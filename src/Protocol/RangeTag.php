<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

use JsonSerializable;

final class RangeTag implements JsonSerializable
{

    public function __construct(
        private string $type,
        private string $text,
        private int $kind,
        private ?RangeData $fullRange,
        private string $detail,
    ) {
    }

    /** @return array<string, int|string|RangeData> */
    public function jsonSerialize(): array
    {
        $data = [
            'type' => $this->type,
            'text' => $this->text,
            'kind' => $this->kind,
        ];

        $data = $this->fullRange !== null
            ? $data + ['fullRange' => $this->fullRange]
            : $data;

        return $this->detail !== ''
            ? $data + ['detail' => $this->detail]
            : $data;
    }
}
