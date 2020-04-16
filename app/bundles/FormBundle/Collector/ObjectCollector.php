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

use Mautic\FormBundle\Collection\ObjectCollection;
use Mautic\FormBundle\Event\ObjectCollectEvent;
use Mautic\FormBundle\FormEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class ObjectCollector implements ObjectCollectorInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var ObjectCollection|null
     */
    private $objects;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function getObjects(): ObjectCollection
    {
        if (null === $this->objects) {
            $this->collect();
        }

        return $this->objects;
    }

    private function collect(): void
    {
        $event = new ObjectCollectEvent();
        $this->dispatcher->dispatch(FormEvents::ON_OBJECT_COLLECT, $event);
        $this->objects = $event->getObjects();
    }
}
