<?php

declare(strict_types=1);

namespace LsifPhp\File;

use function fclose;
use function fopen;
use function fwrite;

final class FileWriter
{
    /** @var resource */
    private $fp;

    public function __construct(private string $filename)
    {
        $fp = fopen($filename, 'w');
        if ($fp === false) {
            throw new CannotOpenFileException($filename);
        }

        $this->fp = $fp;
    }

    public function writeLn(string $line): void
    {
        if (fwrite($this->fp, "{$line}\n") === false) {
            throw new CannotWriteToFileException($this->filename);
        }
    }

    public function close(): void
    {
        fclose($this->fp);
    }
}
