<?php

declare(strict_types=1);

namespace LsifPhp\Parser;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\MixinTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;

use function array_map;
use function count;
use function reset;

final class DocCommentParser
{
    private PhpDocParser $parser;

    private Lexer $lexer;

    public function __construct()
    {
        $constExprParser = new ConstExprParser();
        $typeParser = new TypeParser($constExprParser);
        $this->parser = new PhpDocParser($typeParser, $constExprParser);
        $this->lexer = new Lexer();
    }

    /** @return TypeNode[] */
    public function parseMixins(Node $node): array
    {
        $doc = $node->getDocComment();
        if ($doc === null) {
            return [];
        }
        $docNode = $this->parse($doc);
        $tags = $docNode->getMixinTagValues();
        return array_map(fn(MixinTagValueNode $tag): TypeNode => $tag->type, $tags);
    }

    public function parsePropertyType(Node $node): ?TypeNode
    {
        $doc = $node->getDocComment();
        if ($doc === null) {
            return null;
        }
        $docNode = $this->parse($doc);
        $tags = $docNode->getVarTagValues();
        if (count($tags) === 0) {
            return null;
        }
        return reset($tags)->type;
    }

    public function parseReturnType(Node $node): ?TypeNode
    {
        $doc = $node->getDocComment();
        if ($doc === null) {
            return null;
        }
        $docNode = $this->parse($doc);
        $tags = $docNode->getReturnTagValues();
        if (count($tags) === 0) {
            return null;
        }
        return reset($tags)->type;
    }

    private function parse(Doc $doc): PhpDocNode
    {
        $comment = $doc->getText();
        $tokens = $this->lexer->tokenize($comment);
        $iterator = new TokenIterator($tokens);
        return $this->parser->parse($iterator);
    }
}
