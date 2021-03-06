<?php

declare(strict_types=1);

namespace LsifPhp\Composer;

final class PkgInfo
{
    public function __construct(
        private string $name,
        private string $version,
    ) {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function version(): string
    {
        return $this->version;
    }
}
