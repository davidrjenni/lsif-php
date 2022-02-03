<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

final class Project extends Vertex
{
    public function __construct(int $id, private string $languageId)
    {
        parent::__construct($id, Vertex::LABEL_PROJECT);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return parent::jsonSerialize() + [
            'kind' => $this->languageId,
        ];
    }
}
