<?php

declare(strict_types=1);

namespace LsifPhp\Indexer;

use PhpParser\Node\Stmt;

/** Document represents an LSIF document. */
final class Document
{
    /** @var int[] */
    private array $definitionRangeIds;

    /** @var int[] */
    private array $referenceRangeIds;

    /** @param Stmt[] $stmts */
    public function __construct(
        private int $id,
        private string $path,
        private string $code,
        private array $stmts,
    ) {
        $this->definitionRangeIds = [];
        $this->referenceRangeIds = [];
    }

    public function id(): int
    {
        return $this->id;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function code(): string
    {
        return $this->code;
    }

    /** @return Stmt[] */
    public function stmts(): array
    {
        return $this->stmts;
    }

    public function addDefinitionRangeId(int $rangeId): void
    {
        $this->definitionRangeIds[] = $rangeId;
    }

    /** @return int[] */
    public function definitionRangeIds(): array
    {
        return $this->definitionRangeIds;
    }

    /** @return int[] */
    public function referenceRangeIds(): array
    {
        return $this->referenceRangeIds;
    }

    public function addReferenceRangeId(int $rangeId): void
    {
        $this->referenceRangeIds[] = $rangeId;
    }
}
