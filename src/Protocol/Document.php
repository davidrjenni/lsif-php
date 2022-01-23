<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

final class Document extends Vertex
{
    public function __construct(int $id, private string $uri, private string $languageId)
    {
        parent::__construct($id, Vertex::LABEL_DOCUMENT);
    }

    /** @return array<string, int|string> */
    public function jsonSerialize(): array
    {
        return parent::jsonSerialize() + [
            'uri'        => $this->uri,
            'languageId' => $this->languageId,
        ];
    }
}
