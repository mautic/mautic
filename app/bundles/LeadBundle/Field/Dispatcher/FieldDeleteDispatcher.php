<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Field\Dispatcher;

use Mautic\CoreBundle\Event\EntityPostDelete;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Exception\NoListenerException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class FieldDeleteDispatcher
{
    public function __construct(
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    /**
     * @throws NoListenerException
     */
    public function dispatchPostDeleteEvent(LeadField $entity): EntityPostDelete
    {
        if (!$this->dispatcher->hasListeners(EntityPostDelete::class)) {
            throw new NoListenerException('There is no Listener for this event');
        }

        $event = new EntityPostDelete($entity);
        $this->dispatcher->dispatch($event);

        return $event;
    }
}
