<?php

declare(strict_types=1);

namespace Tests\Composer;

use LsifPhp\Composer\Composer;
use PHPUnit\Framework\TestCase;

final class ComposerTest extends TestCase
{

    private const PROJECT_ROOT = __DIR__  . DIRECTORY_SEPARATOR . 'testdata' . DIRECTORY_SEPARATOR;

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
