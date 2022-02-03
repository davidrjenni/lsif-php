<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

final class PackageInformation extends Vertex
{
    public function __construct(
        int $id,
        private string $manager,
        private string $packageName,
        private string $version,
    ) {
        parent::__construct($id, Vertex::LABEL_PACKAGE_INFORMATION);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return parent::jsonSerialize() + [
            'manager' => $this->manager,
            'name'    => $this->packageName,
            'version' => $this->version,
        ];
    }
}
