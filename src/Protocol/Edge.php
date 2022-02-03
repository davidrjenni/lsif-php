<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

use InvalidArgumentException;

use function in_array;

abstract class Edge extends Element
{
    public const LABEL_CONTAINS = 'contains';

    public const LABEL_NEXT = 'next';

    public const LABEL_TEXT_DOCUMENT_DEFINITION = 'textDocument/definition';

    public const LABEL_TEXT_DOCUMENT_HOVER = 'textDocument/hover';

    public const LABEL_TEXT_DOCUMENT_REFERENCE = 'textDocument/references';

    public const LABEL_ITEM = 'item';

    public const LABEL_MONIKER_EDGE = 'moniker';

    public const LABEL_PACKAGE_INFORMATION = 'packageInformation';

    private const LABELS = [
        self::LABEL_CONTAINS,
        self::LABEL_NEXT,
        self::LABEL_TEXT_DOCUMENT_DEFINITION,
        self::LABEL_TEXT_DOCUMENT_HOVER,
        self::LABEL_TEXT_DOCUMENT_REFERENCE,
        self::LABEL_ITEM,
        self::LABEL_MONIKER_EDGE,
        self::LABEL_PACKAGE_INFORMATION,
    ];

    public function __construct(int $id, private string $label)
    {
        if (!in_array($label, self::LABELS, true)) {
            throw new InvalidArgumentException("$label is not a valid edge label.");
        }

        parent::__construct($id, Element::TYPE_EDGE);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return parent::jsonSerialize() + ['label' => $this->label];
    }
}
