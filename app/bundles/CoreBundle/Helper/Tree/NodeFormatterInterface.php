<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Tree;

interface NodeFormatterInterface
{
    public function format(NodeInterface $node);
}
