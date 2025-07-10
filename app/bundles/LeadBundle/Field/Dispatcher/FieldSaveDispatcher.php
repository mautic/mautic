<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Dispatcher;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Event\LeadFieldEvent;
use Mautic\LeadBundle\Exception\NoListenerException;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FieldSaveDispatcher
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private EntityManager $entityManager,
    ) {
    }

    /**
     * @throws NoListenerException
     */
    public function dispatchPreSaveEvent(LeadField $entity, bool $isNew): LeadFieldEvent
    {
        return $this->dispatchEvent(LeadEvents::FIELD_PRE_SAVE, $entity, $isNew);
    }

    /**
     * @throws NoListenerException
     */
    public function dispatchPostSaveEvent(LeadField $entity, bool $isNew): LeadFieldEvent
    {
        return $this->dispatchEvent(LeadEvents::FIELD_POST_SAVE, $entity, $isNew);
    }

    /**
     * @throws NoListenerException
     */
    public function dispatchEvent(string $action, LeadField $entity, bool $isNew, ?LeadFieldEvent $event = null): LeadFieldEvent
    {
        if (!$this->dispatcher->hasListeners($action)) {
            throw new NoListenerException('There is no Listener for '.$action.' event');
        }

        if (null === $event) {
            $event = new LeadFieldEvent($entity, $isNew);
            $event->setEntityManager($this->entityManager);
        }

        $this->dispatcher->dispatch($event, $action);

        return $event;
    }
}
