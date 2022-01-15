<?php

declare(strict_types=1);

namespace LsifPhp\Parser;

use Closure;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeTraverserInterface;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\NodeVisitorAbstract;

/** NodeTraverserFactory is used to create AST traversers. */
final class NodeTraverserFactory
{

    private ParentConnectingVisitor $parentConnectingVisitor;

    private NameResolver $nameResolver;

    public function __construct()
    {
        $this->parentConnectingVisitor = new ParentConnectingVisitor();
        $this->nameResolver = new NameResolver();
    }

    /**
     * Creates a new AST traverser.
     *
     * @param  Closure(Node, mixed...): void  $visitor
     * @param  mixed...                       $args
     */
    public function create(object $newThis, Closure $visitor, ...$args): NodeTraverserInterface
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor($this->nameResolver);
        $traverser->addVisitor($this->parentConnectingVisitor);
        $traverser->addVisitor(
            new class($newThis, $visitor, $args) extends NodeVisitorAbstract
            {

                /** @var mixed[] $args */
                public function __construct(
                    private object $newThis,
                    private Closure $visitor,
                    private array $args,
                ) {
                }

                public function leaveNode(Node $node): void
                {
                    $this->visitor->call($this->newThis, $node, ...$this->args);
                }
            }
        );

        return $traverser;
    }
}
