<?php

namespace Mautic\CoreBundle\Factory;

use Mautic\CoreBundle\Helper\PageHelperInterface;

interface PageHelperFactoryInterface
{
    public function make(string $sessionPrefix, int $page): PageHelperInterface;
}
