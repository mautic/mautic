<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Tree;

/**
 * @extends \RecursiveIterator<int,NodeInterface>
 */
interface NodeInterface extends \RecursiveIterator
{
    /**
     * @return mixed
     */
    public function getValue();

    public function setParent(NodeInterface $parent): void;

    public function getParent(): ?NodeInterface;

    public function addChild(NodeInterface $child): void;

    /**
     * @return NodeInterface[]
     */
    public function getChildrenArray(): array;

    public function addParam(string $key, mixed $value): void;

    /**
     * @return mixed
     */
    public function getParam(string $key, mixed $default = null);
}
