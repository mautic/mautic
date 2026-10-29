<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\EventListener;

use Mautic\IntegrationsBundle\Event\InternalObjectCreateEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectFindByIdEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectFindEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectOwnerEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectRouteEvent;
use Mautic\IntegrationsBundle\Event\InternalObjectUpdateEvent;
use Mautic\IntegrationsBundle\Sync\DAO\DateRange;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\ContactObjectHelper;
use Mautic\LeadBundle\Exception\ImportFailedException;
use Symfony\Component\Routing\RouterInterface;

final readonly class ContactObjectSubscriber
{
    public function __construct(
        private ContactObjectHelper $contactObjectHelper,
        private RouterInterface $router,
    ) {
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: InternalObjectEvent::class,
        priority: 0
    )]
    public function collectInternalObjects(InternalObjectEvent $event): void
    {
        $event->addObject(new Contact());
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: InternalObjectUpdateEvent::class,
        priority: 0
    )]
    public function updateContacts(InternalObjectUpdateEvent $event): void
    {
        if (Contact::NAME !== $event->getObject()->getName()) {
            return;
        }

        $event->setUpdatedObjectMappings(
            $this->contactObjectHelper->update(
                $event->getIdentifiedObjectIds(),
                $event->getUpdateObjects()
            )
        );
        $event->stopPropagation();
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: InternalObjectCreateEvent::class,
        priority: 0
    )]
    public function createContacts(InternalObjectCreateEvent $event): void
    {
        if (Contact::NAME !== $event->getObject()->getName()) {
            return;
        }

        $event->setObjectMappings($this->contactObjectHelper->create($event->getCreateObjects()));
        $event->stopPropagation();
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: InternalObjectFindEvent::class,
        priority: 0
    )]
    public function findContactsByIds(InternalObjectFindEvent $event): void
    {
        if (Contact::NAME !== $event->getObject()->getName() || empty($event->getIds())) {
            return;
        }

        $event->setFoundObjects($this->contactObjectHelper->findObjectsByIds($event->getIds()));
        $event->stopPropagation();
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: InternalObjectFindEvent::class,
        priority: 0
    )]
    public function findContactsByDateRange(InternalObjectFindEvent $event): void
    {
        if (Contact::NAME !== $event->getObject()->getName() || !$event->getDateRange() instanceof DateRange) {
            return;
        }

        $event->setFoundObjects(
            $this->contactObjectHelper->findObjectsBetweenDates(
                $event->getDateRange()->getFromDate(),
                $event->getDateRange()->getToDate(),
                $event->getStart(),
                $event->getLimit()
            )
        );
        $event->stopPropagation();
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: InternalObjectFindEvent::class,
        priority: 0
    )]
    public function findContactsByFieldValues(InternalObjectFindEvent $event): void
    {
        if (Contact::NAME !== $event->getObject()->getName() || empty($event->getFieldValues())) {
            return;
        }

        $event->setFoundObjects(
            $this->contactObjectHelper->findObjectsByFieldValues(
                $event->getFieldValues()
            )
        );
        $event->stopPropagation();
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: InternalObjectOwnerEvent::class,
        priority: 0
    )]
    public function findOwnerIdsForContacts(InternalObjectOwnerEvent $event): void
    {
        if (Contact::NAME !== $event->getObject()->getName()) {
            return;
        }

        $event->setOwners(
            $this->contactObjectHelper->findOwnerIds(
                $event->getObjectIds()
            )
        );
        $event->stopPropagation();
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: InternalObjectRouteEvent::class,
        priority: 0
    )]
    public function buildContactRoute(InternalObjectRouteEvent $event): void
    {
        if (Contact::NAME !== $event->getObject()->getName()) {
            return;
        }

        $event->setRoute(
            $this->router->generate(
                'mautic_contact_action',
                [
                    'objectAction' => 'view',
                    'objectId'     => $event->getId(),
                ]
            )
        );
        $event->stopPropagation();
    }
    /**
     * @throws ImportFailedException
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: InternalObjectFindByIdEvent::class,
        priority: 0
    )]
    public function findContactById(InternalObjectFindByIdEvent $event): void
    {
        if (null === $event->getId() || Contact::NAME !== $event->getObject()->getName()) {
            return;
        }

        $contact = $this->contactObjectHelper->findObjectById($event->getId());

        if (null === $contact) {
            return;
        }

        $this->contactObjectHelper->setFieldValues($contact);
        $event->setEntity($contact);
    }
}
