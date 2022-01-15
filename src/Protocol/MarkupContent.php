<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

use InvalidArgumentException;

final class MarkupContent implements HoverResultContent
{

    public const KIND_MARKDOWN = 'markdown';

    public const KIND_PLAINTEXT = 'plaintext';

    private const KINDS = [
        self::KIND_MARKDOWN,
        self::KIND_PLAINTEXT,
    ];

    public function __construct(private string $kind, private string $value)
    {
        if (!in_array($kind, self::KINDS, true)) {
            throw new InvalidArgumentException("$kind is not a valid markup content kind.");
        }
    }

    /** @return array<string, string> */
    public function jsonSerialize(): array
    {
        return [
            'kind'  => $this->kind,
            'value' => $this->value,
        ];
    }
}
