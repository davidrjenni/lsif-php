<?php

declare(strict_types=1);

namespace Tests\Types;

use LsifPhp\File\FileReader;
use LsifPhp\Parser\ParserFactory;
use LsifPhp\Types\DefinitionCollector;
use LsifPhp\Types\TypeCollector;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt;
use PhpParser\NodeFinder;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class TypeCollectorTest extends TestCase
{

    private const TEST_DATA_DIRECTORY = __DIR__ . DIRECTORY_SEPARATOR . 'TestData';

    private TypeCollector $typeCollector;

    private NodeFinder $nodeFinder;

    /** @var array<string, Stmt[]> */
    private array $stmts;

    protected function setUp(): void
    {
        parent::setUp();

        $this->nodeFinder = new NodeFinder();
        $this->typeCollector = new TypeCollector();
        $this->stmts = [];

        $parser = ParserFactory::create();
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::TEST_DATA_DIRECTORY)
        );

        $definitionCollector = new DefinitionCollector();

        /** @var SplFileInfo $f */
        foreach ($files as $f) {
            if ($f->getExtension() === 'php') {
                $contents = FileReader::read($f->getRealPath());
                $stmts = $parser->parse($contents);
                $this->stmts[$f->getFilename()] = $stmts;
                $definitionCollector->collect(1, $stmts);
            }
        }

        $defs = $definitionCollector->definitions();
        $this->typeCollector->collect($defs);
    }

    public function testCollect(): void
    {
        $expr = $this->findMethodCall('Class3.php', 'c1m1');
        $type = $this->typeCollector->typeExpr($expr);
        $this->assertEquals(['Tests\Types\TestData\Class2'], $type);

        $expr = $this->findMethodCall('Class3.php', 'c2m1');
        $type = $this->typeCollector->typeExpr($expr);
        $this->assertEmpty($type);

        $expr = $this->findPropertyFetch('Class3.php', 'c3p1');
        $type = $this->typeCollector->typeExpr($expr);
        $this->assertEquals(['Tests\Types\TestData\Class1'], $type);

        $expr = $this->findVariable('Class3.php', 'this');
        $type = $this->typeCollector->typeExpr($expr);
        $this->assertEquals(['Tests\Types\TestData\Class3'], $type);

        $expr = $this->findStaticCall('Class4.php', 'c4m2');
        $type = $this->typeCollector->typeExpr($expr);
        $this->assertEquals(['Tests\Types\TestData\Class4'], $type);

        $expr = $this->findMethodCall('Class4.php', 'c4m4');
        $type = $this->typeCollector->typeExpr($expr);
        $this->assertEquals(['Tests\Types\TestData\Class3'], $type);

        $expr = $this->findPropertyFetch('Class4.php', 'c3p1');
        $type = $this->typeCollector->typeExpr($expr);
        $this->assertEquals(['Tests\Types\TestData\Class1'], $type);

        $expr = $this->findPropertyFetch('Class6.php', 'c6p1');
        $type = $this->typeCollector->typeExpr($expr);
        $this->assertEquals(['Tests\Types\TestData\AbstractClass1'], $type);

        $expr = $this->findMethodCall('Class6.php', 'ac1m1');
        $type = $this->typeCollector->typeExpr($expr);
        $this->assertEquals(['Tests\Types\TestData\Class3'], $type);

        $expr = $this->findPropertyFetch('Class6.php', 'c3p1');
        $type = $this->typeCollector->typeExpr($expr);
        $this->assertEquals(['Tests\Types\TestData\Class1'], $type);

        $expr = $this->findStaticCall('Class6.php', 'c2m2');
        $type = $this->typeCollector->typeExpr($expr);
        $this->assertEquals(['Tests\Types\TestData\Class1'], $type);

        $expr = $this->findPropertyFetch('Class7.php', 'c7p1');
        $type = $this->typeCollector->typeExpr($expr);
        $this->assertEquals(['Tests\Types\TestData\Interface2'], $type);

        $expr = $this->findStaticCall('Class7.php', 'ac1m1');
        $type = $this->typeCollector->typeExpr($expr);
        $this->assertEquals(['Tests\Types\TestData\Class3'], $type);

        $expr = $this->findPropertyFetch('Class8.php', 'c8p1');
        $type = $this->typeCollector->typeExpr($expr);
        $this->assertEquals(['Tests\Types\TestData\Class7'], $type);

        $expr = $this->findMethodCall('Class8.php', 'c7m1');
        $type = $this->typeCollector->typeExpr($expr);
        $this->assertEquals(['Tests\Types\TestData\Class5'], $type);
    }

    private function findVariable(string $filename, string $name): Expr
    {
        $expr = $this->nodeFinder->findFirst(
            $this->stmts[$filename],
            fn (Node $n): bool => $n instanceof Variable && $n->name === $name
        );

        $this->assertNotNull($expr);
        $this->assertInstanceOf(Expr::class, $expr);
        return $expr;
    }

    private function findPropertyFetch(string $filename, string $name): Expr
    {
        $expr = $this->nodeFinder->findFirst(
            $this->stmts[$filename],
            fn (Node $n): bool => $n instanceof PropertyFetch && $n->name->name === $name
        );

        $this->assertNotNull($expr);
        $this->assertInstanceOf(Expr::class, $expr);
        return $expr;
    }

    private function findStaticCall(string $filename, string $name): Expr
    {
        $expr = $this->nodeFinder->findFirst(
            $this->stmts[$filename],
            fn (Node $n): bool => $n instanceof StaticCall && $n->name->name === $name
        );

        $this->assertNotNull($expr);
        $this->assertInstanceOf(Expr::class, $expr);
        return $expr;
    }

    private function findMethodCall(string $filename, string $name): Expr
    {
        $expr = $this->nodeFinder->findFirst(
            $this->stmts[$filename],
            fn (Node $n): bool => $n instanceof MethodCall && $n->name->name === $name
        );

        $this->assertNotNull($expr);
        $this->assertInstanceOf(Expr::class, $expr);
        return $expr;
    }
}
