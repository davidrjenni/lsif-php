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
            new RecursiveDirectoryIterator(__DIR__ . DIRECTORY_SEPARATOR . 'TestData')
        );

        $i = 0;
        $this->documents = [];

        /** @var SplFileInfo $f */
        foreach ($files as $f) {
            if ($f->getExtension() === 'php') {
                $contents = FileReader::read($f->getRealPath());
                $stmts = $parser->parse($contents);
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
        $this->assertDefinition('AbstractClass1::ac1m1', 10, true);
        $this->assertDefinition('AbstractClass1::ac1m2', 12, true);

        $this->assertDefinition('Interface1', 7, true);
        $this->assertDefinition('Interface1::i1m1', 10, true);

        $this->assertDefinition('Interface2', 7, true);

        $this->assertDefinition('Class1', 7, true);
        $this->assertDefinition('Class1::c1m1', 10, true);

        $this->assertDefinition('Class2', 8, true, 'Class2 is a class for testing');
        $this->assertDefinition('Class2::c2m1', 11, true);
        $this->assertDefinition('Class2::c2m2', 15, true);

        $this->assertDefinition('Class4::c4m4', 27, false);

        $this->assertDefinition('Class7::c7p1', 10, false);
        $this->assertDefinition('Class7::__construct::c7p1', 12, false);

        $this->assertDefinition('Class8::C8C2', 13, true, 'Class8 constants');
        $this->assertDefinition('Class8::__construct::c8p1', 16, false);
        $this->assertDefinition('Class8::c8p1', 16, false);
        $this->assertDefinition('Class8::c8m1', 20, false);
        $this->assertDefinition('Class8::c8m2::c8v2', 28, false);
        $this->assertDefinition('Class8::c8m2::anon-func-131::c8v3p1', 30, false);
        $this->assertDefinition('Class8::c8m2::anon-func-152::c8v4v1', 33, false);

        $this->assertDefinition('Class8::c8m2::anon-func-152::anon-class-193::c8v4v2p1', 39, false);
        $this->assertDefinition('Class8::c8m2::anon-func-152::anon-class-193::__construct', 41, true);
        $this->assertDefinition('Class8::c8m2::anon-func-152::anon-class-193::c8v4v2m1', 46, true);
    }

    private function assertDefinition(string $ident, int $startLine, bool $exported, ?string $doc = null)
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
