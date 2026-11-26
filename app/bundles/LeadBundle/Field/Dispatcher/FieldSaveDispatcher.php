<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Dispatcher;

use Mautic\CoreBundle\Event\AbstractEntityEvent;
use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Event\EntityPreSave;
use Mautic\LeadBundle\Entity\LeadField;
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
    public function dispatchPreSaveEvent(LeadField $entity, bool $isNew): EntityPreSave
    {
        $event = new EntityPreSave($entity, $isNew);
        $this->dispatchEvent($event);

        return $event;
    }

    /**
     * @throws NoListenerException
     */
    public function dispatchPostSaveEvent(LeadField $entity, bool $isNew): EntityPostSave
    {
        $event = new EntityPostSave($entity, $isNew);
        $this->dispatchEvent($event);

        return $event;
    }

    /**
     * @throws NoListenerException
     */
    private function dispatchEvent(AbstractEntityEvent $event): void
    {
        if (!$this->dispatcher->hasListeners($event::class)) {
            throw new NoListenerException('There is no Listener for '.$event::class.' event');
        }

        $this->dispatcher->dispatch($event);
    }
}
