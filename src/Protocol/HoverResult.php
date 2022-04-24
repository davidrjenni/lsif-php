<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

final class HoverResult extends Vertex
{
    /** @param HoverResultContent[] $content */
    public function __construct(int $id, private array $content)
    {
        parent::__construct($id, Vertex::LABEL_HOVER_RESULT);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return parent::jsonSerialize() + [
            'result' => ['contents' => $this->content],
        ];
    }
}
