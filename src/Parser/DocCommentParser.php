<?php

declare(strict_types=1);

namespace LsifPhp\Parser;

use PhpParser\Comment\Doc;
use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\MixinTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;

use function array_map;

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

    /** @return TypeNode[] */
    public function parsePropertyTypes(Node $node): array
    {
        $doc = $node->getDocComment();
        if ($doc === null) {
            return [];
        }
        $docNode = $this->parse($doc);
        $tags = $docNode->getVarTagValues();
        return array_map(fn(VarTagValueNode $tag): TypeNode => $tag->type, $tags);
    }

    /** @return TypeNode[] */
    public function parseReturnTypes(Node $node): array
    {
        $doc = $node->getDocComment();
        if ($doc === null) {
            return [];
        }
        $docNode = $this->parse($doc);
        $tags = $docNode->getReturnTagValues();
        return array_map(fn(ReturnTagValueNode $tag): TypeNode => $tag->type, $tags);
    }

    private function parse(Doc $doc): PhpDocNode
    {
        $comment = $doc->getText();
        $tokens = $this->lexer->tokenize($comment);
        $iterator = new TokenIterator($tokens);
        return $this->parser->parse($iterator);
    }
}
