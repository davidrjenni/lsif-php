<?php

declare(strict_types=1);

namespace LsifPhp\Types;

use PhpParser\Comment\Doc;
use PhpParser\Node;

/** Definition represents the defintion of an identifier. */
final class Definition
{

    public function __construct(
        private int $docId,
        private Node $name,
        private string $ident,
        private Node $def,
        private bool $exported,
        private ?Doc $doc,
    ) {
    }

    /** Returns the document ID in which the identifier is defined in. */
    public function docId(): int
    {
        return $this->docId;
    }

    /** Returns the AST node of the identifier's name. */
    public function name(): Node
    {
        return $this->name;
    }

    /** Returns the fully qualified name of the defined identifier. */
    public function identifier(): string
    {
        return $this->ident;
    }

    /** Returns the AST node of the identifier's definition. */
    public function def(): Node
    {
        return $this->def;
    }

    /** Reports whether the definition is non-private. */
    public function exported(): bool
    {
        return $this->exported;
    }

    /** Returns the corresponding doc comment AST node, if any. */
    public function doc(): ?Doc
    {
        return $this->doc;
    }
}
