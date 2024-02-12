<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\Tree;

interface NodeFormatterInterface
{
    /**
     * @return mixed
     */
    public function format(NodeInterface $node);
}
