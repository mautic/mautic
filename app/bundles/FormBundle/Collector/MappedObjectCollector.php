<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Collector;

use Mautic\FormBundle\Collection\MappedObjectCollection;

final class MappedObjectCollector implements MappedObjectCollectorInterface
{
    private FieldCollectorInterface $fieldCollector;

    public function __construct(FieldCollectorInterface $fieldCollector)
    {
        $this->fieldCollector = $fieldCollector;
    }

    public function buildCollection(string ...$objects): MappedObjectCollection
    {
        $mappedObjectCollection = new MappedObjectCollection();

        foreach ($objects as $object) {
            if ($object) {
                $mappedObjectCollection->offsetSet($object, $this->fieldCollector->getFields($object));
            }
        }

        return $mappedObjectCollection;
    }
}
