<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Tree;

interface NodeInterface extends \RecursiveIterator
{
    public function getValue();

    public function setParent(NodeInterface $parent): void;

    public function addChild(NodeInterface $child): void;
}
