<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Collector;

use Mautic\FormBundle\Collection\FieldCollection;

interface FieldCollectorInterface
{
    public function getFields(string $object): FieldCollection;
}
