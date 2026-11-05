<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Dispatcher;

use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Event\FieldPostSaveEvent;
use Mautic\LeadBundle\Event\FieldPreSaveEvent;
use Mautic\LeadBundle\Event\LeadFieldEvent;
use Mautic\LeadBundle\Exception\NoListenerException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class FieldSaveDispatcher
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @throws NoListenerException
     */
    public function dispatchPreSaveEvent(LeadField $entity, bool $isNew): LeadFieldEvent
    {
        return $this->dispatchEvent(new FieldPreSaveEvent($entity, $isNew));
    }

    /**
     * @throws NoListenerException
     */
    public function dispatchPostSaveEvent(LeadField $entity, bool $isNew): LeadFieldEvent
    {
        return $this->dispatchEvent(new FieldPostSaveEvent($entity, $isNew));
    }

    /**
     * @throws NoListenerException
     */
    public function dispatchEvent(LeadFieldEvent $event): LeadFieldEvent
    {
        if (!$this->dispatcher->hasListeners($event::class)) {
            throw new NoListenerException('There is no Listener for '.$event::class.' event');
        }

        $this->dispatcher->dispatch($event);

        return $event;
    }
}
