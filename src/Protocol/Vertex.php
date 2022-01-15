<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

use InvalidArgumentException;

abstract class Vertex extends Element
{
    public const LABEL_META_DATA = 'metaData';

    public const LABEL_PROJECT = 'project';

    public const LABEL_PACKAGE_INFORMATION = 'packageInformation';

    public const LABEL_DOCUMENT = 'document';

    public const LABEL_MONIKER = 'moniker';

    public const LABEL_RANGE = 'range';

    public const LABEL_RESULT_SET = 'resultSet';

    public const LABEL_DEFINITION_RESULT_SET = 'definitionResult';

    public const LABEL_HOVER_RESULT = 'hoverResult';

    public const LABEL_REFERENCE_RESULT = 'referenceResult';

    private const LABELS = [
        self::LABEL_META_DATA,
        self::LABEL_PROJECT,
        self::LABEL_PACKAGE_INFORMATION,
        self::LABEL_DOCUMENT,
        self::LABEL_MONIKER,
        self::LABEL_RANGE,
        self::LABEL_RESULT_SET,
        self::LABEL_DEFINITION_RESULT_SET,
        self::LABEL_HOVER_RESULT,
        self::LABEL_REFERENCE_RESULT,
    ];

    public function __construct(int $id, private string $label)
    {
        if (!in_array($label, self::LABELS, true)) {
            throw new InvalidArgumentException("$label is not a valid vertex label.");
        }

        parent::__construct($id, Element::TYPE_VERTEX);
    }

    /** @return array<string, int|string> */
    public function jsonSerialize(): array
    {
        return parent::jsonSerialize() + ['label' => $this->label];
    }
}
