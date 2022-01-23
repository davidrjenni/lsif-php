<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

use InvalidArgumentException;

use function in_array;

final class Moniker extends Vertex
{
    public const KIND_EXPORT = 'export';

    public const KIND_IMPORT = 'import';

    private const KINDS = [
        self::KIND_EXPORT,
        self::KIND_IMPORT,
    ];

    public function __construct(
        int $id,
        private string $kind,
        private string $scheme,
        private string $identifier,
    ) {
        if (!in_array($kind, self::KINDS, true)) {
            throw new InvalidArgumentException("$kind is not a valid moniker kind.");
        }

        parent::__construct($id, Vertex::LABEL_MONIKER);
    }

    /** @return array<string, int|string> */
    public function jsonSerialize(): array
    {
        return parent::jsonSerialize() + [
            'kind'       => $this->kind,
            'scheme'     => $this->scheme,
            'identifier' => $this->identifier,
        ];
    }
}
