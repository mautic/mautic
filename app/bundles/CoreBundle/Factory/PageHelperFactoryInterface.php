<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Factory;

use Mautic\CoreBundle\Helper\PageHelperInterface;

interface PageHelperFactoryInterface
{
    public function make(string $sessionPrefix, int $page): PageHelperInterface;
}
