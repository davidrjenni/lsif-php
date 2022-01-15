<?php

declare(strict_types=1);

namespace LsifPhp\File;

use Exception;

use function file_get_contents;
use function is_file;

final class FileReader
{

    public static function read(string $filename): string
    {
        if (!is_file($filename)) {
            throw new CannotReadFileException($filename);
        }

        $contents = file_get_contents($filename);
        if ($contents === false) {
            throw new CannotReadFileException($filename);
        }

        return $contents;
    }
}
