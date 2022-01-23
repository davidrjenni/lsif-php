<?php

declare(strict_types=1);

namespace LsifPhp\Indexer;

/** Definition represents an LSIF definition. */
final class Definition
{
    /** @var array<int, int[]> */
    private array $referenceRangeIds;

    public function __construct(
        private int $docId,
        private int $rangeId,
        private int $resultSetId,
    ) {
        $this->referenceRangeIds = [];
    }

    public function docId(): int
    {
        return $this->docId;
    }

    public function rangeId(): int
    {
        return $this->rangeId;
    }

    public function resultSetId(): int
    {
        return $this->resultSetId;
    }

    public function addReferenceRangeId(int $docId, int $rangeId): void
    {
        if (!isset($this->referenceRangeIds[$docId])) {
            $this->referenceRangeIds[$docId] = [];
        }
        $this->referenceRangeIds[$docId][] = $rangeId;
    }

    /** @return array<int, int[]> */
    public function referenceRangeIds(): array
    {
        return $this->referenceRangeIds;
    }
}
