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

    public function testDependency(): void
    {
        $composer = Composer::parse(self::PROJECT_ROOT . 'project1');

        $pkg = $composer->dependency("Foo\\Bar");
        $this->assertNull($pkg);

        $pkg = $composer->dependency("Dep\\Dep1\\Foo\\C1::c1p1");
        $this->assertEquals('dependency/dep1', $pkg->name());
        $this->assertEquals('v4.13.2', $pkg->version());

        $pkg = $composer->dependency("Dep\\Dep1\\Bar\\Baz\\C2::c2m1()");
        $this->assertEquals('dependency/dep1', $pkg->name());
        $this->assertEquals('v4.13.2', $pkg->version());

        $pkg = $composer->dependency("Dep\\Dep2\\Bar\\C1::c1m1()");
        $this->assertNull($pkg);

        $pkg = $composer->dependency("Dep\\Dev\\Dep1\\Bar\\Baz\\C2::c2m1()");
        $this->assertEquals('dependency/devdep1', $pkg->name());
        $this->assertEquals('1.0.1', $pkg->version());
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
