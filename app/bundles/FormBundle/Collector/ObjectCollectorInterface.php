<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Collector;

use Mautic\FormBundle\Collection\ObjectCollection;

interface ObjectCollectorInterface
{
    public function getObjects(): ObjectCollection;
}
