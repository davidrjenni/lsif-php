<?php

declare(strict_types=1);

namespace LsifPhp\Parser;

use PhpParser\Lexer;
use PhpParser\Parser;
use PhpParser\ParserFactory as PhpParserFactory;

/** ParserFactory is used to create PHP parsers. */
final class ParserFactory
{
    /** Creates a PHP parser, configured to produce AST nodes with position information. */
    public static function create(): Parser
    {
        return (new PhpParserFactory())->create(
            PhpParserFactory::ONLY_PHP7,
            new Lexer(
                [
                    'usedAttributes' => [
                        'comments',
                        'startLine',
                        'endLine',
                        'startTokenPos',
                        'endTokenPos',
                        'startFilePos',
                        'endFilePos',
                    ],
                ],
            ),
        );
    }
}
