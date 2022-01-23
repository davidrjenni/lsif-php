<?php

declare(strict_types=1);

namespace LsifPhp\Protocol;

use InvalidArgumentException;
use JsonSerializable;
use PhpParser\Node;

use function strlen;
use function strrpos;

final class Pos implements JsonSerializable
{
    public static function start(Node $node, string $code): Pos
    {
        return new Pos(
            $node->getStartLine() - 1,
            self::toColumn($code, $node->getStartFilePos()) - 1
        );
    }

    public static function end(Node $node, string $code): Pos
    {
        return new Pos(
            $node->getEndLine() - 1,
            self::toColumn($code, $node->getEndFilePos())
        );
    }

    private static function toColumn(string $code, int $filePos): int
    {
        $codeLength = strlen($code);
        if ($filePos > $codeLength) {
            throw new InvalidArgumentException('Invalid position information.');
        }

        $lineStartPos = strrpos($code, "\n", $filePos - $codeLength);
        if ($lineStartPos === false) {
            $lineStartPos = -1;
        }

        return $filePos - $lineStartPos;
    }

    public function __construct(private int $line, private int $char)
    {
    }

    /** @return array<string, int> */
    public function jsonSerialize(): array
    {
        return [
            'line'      => $this->line,
            'character' => $this->char,
        ];
    }
}
