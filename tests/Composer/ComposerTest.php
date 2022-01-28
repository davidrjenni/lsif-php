<?php

declare(strict_types=1);

namespace Tests\Composer;

use LsifPhp\Composer\CannotReadComposerPropertyException;
use LsifPhp\Composer\Composer;
use PHPUnit\Framework\TestCase;

use const DIRECTORY_SEPARATOR;

final class ComposerTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__  . DIRECTORY_SEPARATOR . 'testdata' . DIRECTORY_SEPARATOR;

    public function testPkgName(): void
    {
        $composer = Composer::parse(self::PROJECT_ROOT . 'project1');
        $name = $composer->pkgName();

        $this->assertEquals('davidrjenni/test-project1', $name);
    }

    public function testPkgNameNotFound(): void
    {
        $composer = Composer::parse(self::PROJECT_ROOT . 'project3');

        $this->expectException(CannotReadComposerPropertyException::class);
        $this->expectExceptionMessage("Cannot read property 'name' from composer.json.");

        $composer->pkgName();
    }

    public function testSourceDirs(): void
    {
        $tests = [
            'project1' => ['src/', 'database/', 'tests/'],
            'project2' => ['src/'],
            'project3' => ['src/', 'database/'],
        ];

        foreach ($tests as $project => $expectedDirs) {
            $composer = Composer::parse(self::PROJECT_ROOT . $project);
            $actualDirs = $composer->sourceDirs();

            $this->assertEquals($actualDirs, $expectedDirs);
        }
    }
}
