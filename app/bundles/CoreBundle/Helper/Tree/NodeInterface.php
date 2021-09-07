<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Tree;

interface NodeInterface extends \RecursiveIterator
{
    public function getValue();

    public function setParent(NodeInterface $parent): void;

    public function getParent(): ?NodeInterface;

    public function addChild(NodeInterface $child): void;

    public function getChildrenArray(): array;

    public function addParam(string $key, $value): void;

    public function getParam(string $key, $default = null);
}
