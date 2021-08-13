<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Tree;

class IntNode implements NodeInterface
{
    /**
     * @var int
     */
    private $value;

    /**
     * @var NodeInterface|null
     */
    private $parent;

    /**
     * @var NodeInterface[]
     */
    private $children = [];

    /**
     * @var int
     */
    private $iteratorKey = 0;

    public function __construct(int $value, NodeInterface $parent = null)
    {
        $this->value  = $value;
        $this->parent = $parent;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getParent(): ?NodeInterface
    {
        return $this->parent;
    }

    public function setParent(NodeInterface $parent): void
    {
        $this->parent = $parent;
    }

    public function addChild(NodeInterface $child): void
    {
        $child->setParent($this);

        $this->children[] = $child;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    public function current(): NodeInterface
    {
        return $this->children[$this->iteratorKey];
    }

    public function key(): int
    {
        return $this->iteratorKey;
    }

    public function next(): void
    {
        ++$this->iteratorKey;
    }

    public function rewind(): void
    {
        --$this->iteratorKey;
    }

    public function valid(): bool
    {
        return isset($this->children[$this->iteratorKey]);
    }
}
