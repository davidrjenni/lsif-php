<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

use LsifPhp\File\FileWriter;

use function json_encode;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

/** Emitter emits LSIF data. */
final class Emitter
{
    private int $id;

    private FileWriter $writer;

    public function __construct(string $filename = 'dump.lsif')
    {
        $this->id = 0;
        $this->writer = new FileWriter($filename);
    }

    public function write(): void
    {
        $this->writer->close();
    }

    public function emitMetaData(string $projectRoot, ToolInfo $toolInfo): void
    {
        $this->emit(new MetaData($this->id(), "file://$projectRoot", $toolInfo));
    }

    public function emitProject(string $languageId): int
    {
        return $this->emit(new Project($this->id(), $languageId));
    }

    public function emitDocument(string $filename, string $languageId): int
    {
        return $this->emit(new Document($this->id(), "file://$filename", $languageId));
    }

    public function emitExportMoniker(string $scheme, string $identifier): int
    {
        return $this->emit(new Moniker($this->id(), Moniker::KIND_EXPORT, $scheme, $identifier));
    }

    public function emitImportMoniker(string $scheme, string $identifier): int
    {
        return $this->emit(new Moniker($this->id(), Moniker::KIND_IMPORT, $scheme, $identifier));
    }

    public function emitMonikerEdge(int $outV, int $inV): void
    {
        $this->emit(new MonikerEdge($this->id(), $outV, $inV));
    }

    public function emitPackageInformation(string $manager, string $name, string $version): int
    {
        return $this->emit(new PackageInformation($this->id(), $manager, $name, $version));
    }

    public function emitPackageInformationEdge(int $outV, int $inV): void
    {
        $this->emit(new PackageInformationEdge($this->id(), $outV, $inV));
    }

    public function emitRange(Pos $start, Pos $end): int
    {
        return $this->emit(new Range($this->id(), $start, $end));
    }

    public function emitResultSet(): int
    {
        return $this->emit(new ResultSet($this->id()));
    }

    public function emitDefinitionResult(): int
    {
        return $this->emit(new DefinitionResult($this->id()));
    }

    /** @param HoverResultContent[] $content */
    public function emitHoverResult(array $content): int
    {
        return $this->emit(new HoverResult($this->id(), $content));
    }

    public function emitReferenceResult(): int
    {
        return $this->emit(new ReferenceResult($this->id()));
    }

    public function emitTextDocumentDefinition(int $outV, int $inV): void
    {
        $this->emit(new TextDocumentDefinition($this->id(), $outV, $inV));
    }

    public function emitTextDocumentHover(int $outV, int $inV): void
    {
        $this->emit(new TextDocumentHover($this->id(), $outV, $inV));
    }

    public function emitTextDocumentReference(int $outV, int $inV): void
    {
        $this->emit(new TextDocumentReference($this->id(), $outV, $inV));
    }

    public function emitNext(int $outV, int $inV): void
    {
        $this->emit(new Next($this->id(), $outV, $inV));
    }

    /** @param int[] $inVs */
    public function emitItem(int $outV, array $inVs, int $documentId): void
    {
        $this->emit(new Item($this->id(), $outV, $inVs, $documentId));
    }

    /** @param int[] $inVs */
    public function emitItemOfDefinitions(int $outV, array $inVs, int $documentId): void
    {
        $this->emit(new Item($this->id(), $outV, $inVs, $documentId, Item::PROPERTY_DEFINITIONS));
    }

    /** @param int[] $inVs */
    public function emitItemOfReferences(int $outV, array $inVs, int $documentId): void
    {
        $this->emit(new Item($this->id(), $outV, $inVs, $documentId, Item::PROPERTY_REFERENCES));
    }

    /** @param int[] $inVs */
    public function emitContains(int $outV, array $inVs): void
    {
        $this->emit(new Contains($this->id(), $outV, $inVs));
    }

    private function emit(Element $element): int
    {
        $line = json_encode($element, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $this->writer->writeLn($line);
        return $element->id();
    }

    private function id(): int
    {
        $this->id++;
        return $this->id;
    }
}
