<?php

declare(strict_types=1);

namespace Tests\Indexer;

use LsifPhp\File\FileReader;
use LsifPhp\Indexer\Indexer;
use LsifPhp\Protocol\Emitter;
use LsifPhp\Protocol\ToolInfo;
use PHPUnit\Framework\TestCase;

use function sys_get_temp_dir;
use function tempnam;

use const DIRECTORY_SEPARATOR;

final class IndexerTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__  . DIRECTORY_SEPARATOR . 'TestData';

    private string $tmpfile;

    private Emitter $emitter;

    private Indexer $indexer;

    protected function setUp(): void
    {
        parent::setUp();

        $tmpfile = tempnam(sys_get_temp_dir(), 'lsif-php-indexer-test');
        $this->assertNotFalse($tmpfile);

        $this->tmpfile = $tmpfile;

        $toolInfo = new ToolInfo('lsif-php', 'dev', []);
        $this->emitter = new Emitter($this->tmpfile);
        $this->indexer = new Indexer(self::PROJECT_ROOT, $this->emitter, $toolInfo, 'v0.1.1');
    }

    public function testIndex(): void
    {
        $this->indexer->index();
        $this->emitter->write();

        $lsif = FileReader::read($this->tmpfile);

        $this->assertStringContainsString(
            '{"id":1,"type":"vertex","label":"metaData"',
            $lsif,
        );

        $this->assertStringContainsString(
            '{"id":2,"type":"vertex","label":"project","kind":"php"}',
            $lsif,
        );

        $this->assertStringContainsString(
            '"type":"vertex","label":"document","uri":"file://' . self::PROJECT_ROOT . '/src/Class1.php","languageId":"php"}',
            $lsif,
        );

        $this->assertStringContainsString(
            '"type":"vertex","label":"document","uri":"file://' . self::PROJECT_ROOT . '/src/Class2.php","languageId":"php"}',
            $lsif,
        );

        $this->assertStringContainsString(
            '"type":"vertex","label":"moniker","kind":"export","scheme":"composer","identifier":"TestProject\\\\Class1::foo()"}',
            $lsif,
        );

        $this->assertStringContainsString(
            '"type":"vertex","label":"packageInformation","manager":"composer","name":"davidrjenni/testdep","version":"v4.13.2"}',
            $lsif,
        );

        $this->assertStringContainsString(
            '"type":"vertex","label":"moniker","kind":"import","scheme":"composer","identifier":"TestProjectDep\\\\Foo"}',
            $lsif,
        );
    }
}
