<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Tree;

class IntNode implements NodeInterface
{
    /**
     * @var NodeInterface[]
     */
    private array $children = [];

    /**
     * @var array<string,mixed>
     */
    private array $params = [];

    private int $position = 0;

    public function __construct(
        private int $value,
        private ?NodeInterface $parent = null
    ) {
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

    public function addParam(string $key, $value): void
    {
        $this->params[$key] = $value;
    }

    public function getParam(string $key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }

    public function getChildrenArray(): array
    {
        return $this->children;
    }

    public function getChildren(): NodeInterface
    {
        return $this->current();
    }

    public function hasChildren(): bool
    {
        return !empty($this->current()->getChildrenArray());
    }

    public function current(): NodeInterface
    {
        return $this->children[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function valid(): bool
    {
        return isset($this->children[$this->position]);
    }
}
