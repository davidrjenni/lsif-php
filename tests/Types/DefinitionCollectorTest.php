<?php

declare(strict_types=1);

namespace Tests\Types;

use LsifPhp\File\FileReader;
use LsifPhp\Parser\ParserFactory;
use LsifPhp\Types\Definition;
use LsifPhp\Types\DefinitionCollector;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function explode;

use const DIRECTORY_SEPARATOR;

final class DefinitionCollectorTest extends TestCase
{
    /** @var array<string, int> */
    private array $documents;

    /** @var array<string, Definition> */
    private array $definitions;

    protected function setUp(): void
    {
        parent::setUp();

        $definitionCollector = new DefinitionCollector();
        $parser = ParserFactory::create();
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . DIRECTORY_SEPARATOR . 'TestData'),
        );

        $i = 0;
        $this->documents = [];

        /** @var SplFileInfo $f */
        foreach ($files as $f) {
            if ($f->getExtension() === 'php') {
                $filename = $f->getRealPath();
                $this->assertNotFalse($filename);

                $contents = FileReader::read($filename);
                $stmts = $parser->parse($contents);
                $this->assertNotNull($stmts);

                $definitionCollector->collect($i, $stmts);
                $this->documents[$f->getFilename()] = $i;
                $i++;
            }
        }

        $this->definitions = [];
        foreach ($definitionCollector->definitions() as $def) {
            $this->definitions[$def->identifier()] = $def;
        }
    }

    public function testCollectDefinitions(): void
    {
        $this->assertDefinition('AbstractClass1', 7, true);
        $this->assertDefinition('AbstractClass1::ac1m1()', 9, true);
        $this->assertDefinition('AbstractClass1::ac1m2()', 11, true);

        $this->assertDefinition('Interface1', 7, true);
        $this->assertDefinition('Interface1::i1m1()', 9, true);

        $this->assertDefinition('Interface2', 7, true);

        $this->assertDefinition('Class1', 7, true);
        $this->assertDefinition('Class1::c1m1()', 9, true);

        $this->assertDefinition('Class2', 8, true, 'Class2 is a class for testing');
        $this->assertDefinition('Class2::c2m1()', 10, true);
        $this->assertDefinition('Class2::c2m2()', 15, true);
        $this->assertDefinition('Class2::c2m3()::k', 23, false);
        $this->assertDefinition('Class2::c2m3()::v', 23, false);
        $this->assertDefinition('Class2::c2m3()::w', 27, false);
        $this->assertDefinition('Class2::c2m3()::i', 31, false);
        $this->assertDefinition('Class2::c2m3()::r', 35, false);
        $this->assertDefinition('Class2::c2m3()::g', 35, false);
        $this->assertDefinition('Class2::c2m3()::b', 35, false);
        $this->assertDefinition('Class2::c2m3()::x', 38, false);
        $this->assertDefinition('Class2::c2m3()::y', 38, false);
        $this->assertDefinition('Class2::c2m3()::z', 38, false);

        $this->assertDefinition('Class3::c3p1', 9, true);

        $this->assertDefinition('Class4::c4m4()', 26, false);

        $this->assertDefinition('Class7::c7p1', 9, false);
        $this->assertDefinition('Class7::__construct()::c7p1', 11, false);

        $this->assertDefinition('Class8::C8C2', 12, true, 'Class8 constants');
        $this->assertDefinition('Class8::__construct()::c8p1', 15, false);
        $this->assertDefinition('Class8::c8p1', 15, false);
        $this->assertDefinition('Class8::c8m1()', 19, false);
        $this->assertDefinition('Class8::c8m2()::c8v2', 27, false);
        $this->assertDefinition('Class8::c8m2()::anon-func-131()::c8v3p1', 29, false);
        $this->assertDefinition('Class8::c8m2()::anon-func-152()::c8v4v1', 32, false);

        $this->assertDefinition('Class8::c8m2()::anon-func-152()::anon-class-198::c8v4v2p1', 37, false);
        $this->assertDefinition('Class8::c8m2()::anon-func-152()::anon-class-198::__construct()', 39, true);
        $this->assertDefinition('Class8::c8m2()::anon-func-152()::anon-class-198::c8v4v2m1()', 44, true);

        $this->assertDefinition('Interface1::i1m1()', 9, true);

        $this->assertDefinition('Trait1::t1m1()', 11, true);
        $this->assertDefinition('Trait2::t2m1()', 11, true);
        $this->assertDefinition('Trait2::t2m1()::v1', 13, false);
    }

    private function assertDefinition(string $ident, int $startLine, bool $exported, ?string $doc = null): void
    {
        $def = $this->definitions["Tests\\Types\\TestData\\$ident"] ?? null;
        $this->assertNotNull($def);

        $this->assertEquals($startLine, $def->name()->getStartLine());
        $this->assertEquals($exported, $def->exported());

        $filename = explode('::', $ident)[0] ?? '';
        $this->assertEquals($this->documents["$filename.php"], $def->docId());

        if ($doc === null) {
            $this->assertNull($def->doc());
        } else {
            $this->assertNotNull($def->doc());
            $this->assertStringContainsString($doc, $def->doc()->getText());
        }
    }
}
