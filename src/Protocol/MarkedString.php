<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

final class MarkedString implements HoverResultContent
{
    public function __construct(private string $language, private string $value)
    {
    }

    /** @return array<string, string> */
    public function jsonSerialize(): array
    {
        return [
            'language' => $this->language,
            'value'    => $this->value,
        ];
    }
}
