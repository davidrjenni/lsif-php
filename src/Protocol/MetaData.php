<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

final class MetaData extends Vertex
{
    private const VERSION = '0.4.3';

    private const POSITION_ENCODING = 'utf-16';

    private string $version;

    private string $positionEncoding;

    public function __construct(int $id, private string $projectRoot, private ToolInfo $toolInfo)
    {
        parent::__construct($id, Vertex::LABEL_META_DATA);
        $this->version = self::VERSION;
        $this->positionEncoding = self::POSITION_ENCODING;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return parent::jsonSerialize() + [
            'version'          => $this->version,
            'projectRoot'      => $this->projectRoot,
            'positionEncoding' => $this->positionEncoding,
            'toolInfo'         => $this->toolInfo,
        ];
    }
}
