<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

use InvalidArgumentException;
use JsonSerializable;

use function in_array;

abstract class Element implements JsonSerializable
{
    public const TYPE_EDGE = 'edge';

    public const TYPE_VERTEX = 'vertex';

    private const TYPES = [
        self::TYPE_EDGE,
        self::TYPE_VERTEX,
    ];

    public function __construct(private int $id, private string $type)
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException("$type is not a valid element type.");
        }
    }

    public function id(): int
    {
        return $this->id;
    }

    /** @return array<string, int|string|JsonSerializable|int[]|array<string, HoverResultContent[]>> */
    public function jsonSerialize(): array
    {
        return [
            'id'   => $this->id,
            'type' => $this->type,
        ];
    }
}
