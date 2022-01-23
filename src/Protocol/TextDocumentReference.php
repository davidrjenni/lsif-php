<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

final class TextDocumentReference extends Edge
{
    public function __construct(
        int $id,
        private int $outV,
        private int $inV,
    ) {
        parent::__construct($id, Edge::LABEL_TEXT_DOCUMENT_REFERENCE);
    }

    /** @return array<string, int|string> */
    public function jsonSerialize(): array
    {
        return parent::jsonSerialize() + [
            'outV' => $this->outV,
            'inV'  => $this->inV,
        ];
    }
}
