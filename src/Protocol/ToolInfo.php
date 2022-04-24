<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

use JsonSerializable;

final class ToolInfo implements JsonSerializable
{
    /** @param string[] $args */
    public function __construct(
        private string $name,
        private string $version,
        private array $args,
    ) {
    }

    /** @return array<string, string|string[]> */
    public function jsonSerialize(): array
    {
        return [
            'name'    => $this->name,
            'version' => $this->version,
            'args'    => $this->args,
        ];
    }
}
