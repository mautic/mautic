<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Collector;

use Mautic\FormBundle\Collection\MappedObjectCollection;

interface MappedObjectCollectorInterface
{
    public function buildCollection(string ...$objects): MappedObjectCollection;
}
