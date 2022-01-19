<?php

declare(strict_types=1);

namespace LsifPhp\File;

use Exception;

final class CannotReadFileException extends Exception
{

    public function __construct(string $filename)
    {
        parent::__construct("Cannot read file: {$filename}");
    }
}
