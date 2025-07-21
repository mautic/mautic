<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Dispatcher;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Event\LeadFieldEvent;
use Mautic\LeadBundle\Exception\NoListenerException;
use Mautic\LeadBundle\Field\Exception\AbortColumnUpdateException;
use Mautic\LeadBundle\Field\Settings\BackgroundSettings;
use Mautic\LeadBundle\LeadEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FieldDeleteDispatcher
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
        private EntityManager $entityManager,
        private BackgroundSettings $backgroundSettings,
    ) {
    }

    /**
     * @throws NoListenerException
     * @throws AbortColumnUpdateException
     */
    public function dispatchPreDeleteEvent(LeadField $entity): LeadFieldEvent
    {
        if ($this->backgroundSettings->shouldProcessColumnChangeInBackground()) {
            throw new AbortColumnUpdateException('Column change will be processed in background job');
        }

        return $this->dispatchEvent(LeadEvents::FIELD_PRE_DELETE, $entity);
    }

    /**
     * @throws NoListenerException
     */
    public function dispatchPostDeleteEvent(LeadField $entity): LeadFieldEvent
    {
        return $this->dispatchEvent(LeadEvents::FIELD_POST_DELETE, $entity);
    }

    /**
     * @param string $action - Use constant from LeadEvents class (e.g. LeadEvents::FIELD_PRE_SAVE)
     *
     * @throws NoListenerException
     */
    private function dispatchEvent($action, LeadField $entity, ?LeadFieldEvent $event = null): LeadFieldEvent
    {
        if (!$this->dispatcher->hasListeners($action)) {
            throw new NoListenerException('There is no Listener for this event');
        }

        if (null === $event) {
            $event = new LeadFieldEvent($entity);
            $event->setEntityManager($this->entityManager);
        }

        $this->dispatcher->dispatch($event, $action);

        return $event;
    }
}
