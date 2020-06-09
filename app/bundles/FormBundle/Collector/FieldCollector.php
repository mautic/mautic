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

use Mautic\FormBundle\Collection\FieldCollection;
use Mautic\FormBundle\Event\FieldCollectEvent;
use Mautic\FormBundle\FormEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class FieldCollector implements FieldCollectorInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var FieldCollection[]
     */
    private $fieldCollections = [];

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function getFields(string $object): FieldCollection
    {
        if (!isset($this->fieldCollections[$object])) {
            $this->collect($object);
        }

        return $this->fieldCollections[$object];
    }

    private function collect(string $object): void
    {
        $event = new FieldCollectEvent($object);
        $this->dispatcher->dispatch(FormEvents::ON_FIELD_COLLECT, $event);
        $this->fieldCollections[$object] = $event->getFields();
    }
}
