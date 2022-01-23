<?php

declare(strict_types=1);

namespace Tests\Indexer;

use LsifPhp\Indexer\Indexer;
use LsifPhp\Protocol\Emitter;
use LsifPhp\Protocol\ToolInfo;
use PHPUnit\Framework\TestCase;

final class IndexerTest extends TestCase
{
    private const PROJECT_ROOT = __DIR__  . DIRECTORY_SEPARATOR . 'TestData';

    private Emitter $emitter;

    private Indexer $indexer;

    protected function setUp(): void
    {
        parent::setUp();

        $toolInfo = new ToolInfo('lsif-php', 'dev', []);
        $this->emitter = new Emitter();
        $this->indexer = new Indexer(self::PROJECT_ROOT, $this->emitter, $toolInfo);
    }

    public function testIndex(): void
    {
        $this->indexer->index();
        $lsif = $this->emitter->write();

        $this->assertStringContainsString(
            '{"id":1,"type":"vertex","label":"metaData"',
            $lsif
        );

        $this->assertStringContainsString(
            '{"id":2,"type":"vertex","label":"project","kind":"php"}',
            $lsif
        );

        $this->assertStringContainsString(
            '"type":"vertex","label":"document","uri":"file://' . self::PROJECT_ROOT . '/src/Class1.php","languageId":"php"}',
            $lsif
        );

        $this->assertStringContainsString(
            '"type":"vertex","label":"document","uri":"file://' . self::PROJECT_ROOT . '/src/Class2.php","languageId":"php"}',
            $lsif
        );
    }
}
