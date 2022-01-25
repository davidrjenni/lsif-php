<?php

declare(strict_types=1);

namespace LsifPhp\File;

use Exception;

final class CannotWriteToFileException extends Exception
{
    public function __construct(string $filename)
    {
        parent::__construct("Cannot write to file: {$filename}");
    }
}
