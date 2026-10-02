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

    public function setParent(self $parent): void;

    public function getParent(): ?self;

    public function addChild(self $child): void;

    /**
     * @return NodeInterface[]
     */
    public function getChildrenArray(): array;

    /**
     * @param mixed $value
     */
    public function addParam(string $key, $value): void;

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    public function getParam(string $key, $default = null);
}
