<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

use InvalidArgumentException;

use function in_array;

final class Item extends Edge
{
    public const PROPERTY_DEFINITIONS = 'definitions';

    public const PROPERTY_REFERENCES = 'references';

    private const PROPERTIES = [
        self::PROPERTY_DEFINITIONS,
        self::PROPERTY_REFERENCES,
    ];

    /** @param int[] $inVs */
    public function __construct(
        int $id,
        private int $outV,
        private array $inVs,
        private int $documentId,
        private string $property = '',
    ) {
        if ($property !== '' && !in_array($property, self::PROPERTIES, true)) {
            throw new InvalidArgumentException("{$property} is not a valid item property.");
        }

        parent::__construct($id, Edge::LABEL_ITEM);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize() + [
            'outV'     => $this->outV,
            'inVs'     => $this->inVs,
            'document' => $this->documentId,
        ];

        return $this->property !== ''
            ? $data + ['property' => $this->property]
            : $data;
    }
}
