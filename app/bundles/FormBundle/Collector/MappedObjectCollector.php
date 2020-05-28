<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Collector;

use Mautic\FormBundle\Collection\MappedObjectCollection;

final class MappedObjectCollector implements MappedObjectCollectorInterface
{
    /**
     * @var FieldCollectorInterface
     */
    private $fieldCollector;

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
