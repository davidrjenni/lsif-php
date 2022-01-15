<?php

declare(strict_types=1);

namespace Tests\File;

use LsifPhp\File\CannotReadFileException;
use LsifPhp\File\FileReader;
use PHPUnit\Framework\TestCase;

final class FileReaderTest extends TestCase
{

    public function testRead(): void
    {
        $contents = FileReader::read(__DIR__ . DIRECTORY_SEPARATOR . 'testdata' . DIRECTORY_SEPARATOR . 'test-file.txt');

        $this->assertEquals("The quick brown fox jumps\nover the lazy dog", $contents);
    }

    public function testReadFails(): void
    {
        $this->expectException(CannotReadFileException::class);
        $this->expectExceptionMessage('Cannot read file: non-existent.txt');

        FileReader::read('non-existent.txt');
    }
}