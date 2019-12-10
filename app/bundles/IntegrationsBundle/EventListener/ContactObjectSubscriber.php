<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\EventListener;

use MauticPlugin\IntegrationsBundle\Event\InternalObjectCreateEvent;
use MauticPlugin\IntegrationsBundle\Event\InternalObjectEvent;
use MauticPlugin\IntegrationsBundle\Event\InternalObjectFindEvent;
use MauticPlugin\IntegrationsBundle\Event\InternalObjectOwnerEvent;
use MauticPlugin\IntegrationsBundle\Event\InternalObjectRouteEvent;
use MauticPlugin\IntegrationsBundle\Event\InternalObjectUpdateEvent;
use MauticPlugin\IntegrationsBundle\IntegrationEvents;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\ContactObjectHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Router;

class ContactObjectSubscriber implements EventSubscriberInterface
{
    /**
     * @var ContactObjectHelper
     */
    private $contactObjectHelper;

    /**
     * @var Router
     */
    private $router;

    /**
     * @param ContactObjectHelper $contactObjectHelper
     * @param Router              $router
     */
    public function __construct(
        ContactObjectHelper $contactObjectHelper,
        Router $router
    ) {
        $this->contactObjectHelper = $contactObjectHelper;
        $this->router              = $router;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            IntegrationEvents::INTEGRATION_COLLECT_INTERNAL_OBJECTS => ['collectInternalObjects', 0],
            IntegrationEvents::INTEGRATION_UPDATE_INTERNAL_OBJECTS  => ['updateContacts', 0],
            IntegrationEvents::INTEGRATION_CREATE_INTERNAL_OBJECTS  => ['createContacts', 0],
            IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORDS    => [
                ['findContactsByIds', 0],
                ['findContactsByDateRange', 0],
                ['findContactsByFieldValues', 0],
            ],
            IntegrationEvents::INTEGRATION_FIND_OWNER_IDS              => ['findOwnerIdsForContacts', 0],
            IntegrationEvents::INTEGRATION_BUILD_INTERNAL_OBJECT_ROUTE => ['buildContactRoute', 0],
        ];
    }

    /**
     * @param InternalObjectEvent $event
     */
    public function collectInternalObjects(InternalObjectEvent $event): void
    {
        $event->addObject(new Contact());
    }

    /**
     * @param InternalObjectUpdateEvent $event
     */
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

    /**
     * @param InternalObjectCreateEvent $event
     */
    public function createContacts(InternalObjectCreateEvent $event): void
    {
        if (Contact::NAME !== $event->getObject()->getName()) {
            return;
        }

        $event->setObjectMappings($this->contactObjectHelper->create($event->getCreateObjects()));
        $event->stopPropagation();
    }

    /**
     * @param InternalObjectFindEvent $event
     */
    public function findContactsByIds(InternalObjectFindEvent $event): void
    {
        if (Contact::NAME !== $event->getObject()->getName() || empty($event->getIds())) {
            return;
        }

        $event->setFoundObjects($this->contactObjectHelper->findObjectsByIds($event->getIds()));
        $event->stopPropagation();
    }

    /**
     * @param InternalObjectFindEvent $event
     */
    public function findContactsByDateRange(InternalObjectFindEvent $event): void
    {
        if (Contact::NAME !== $event->getObject()->getName() || empty($event->getDateRange())) {
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

    /**
     * @param InternalObjectFindEvent $event
     */
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

    /**
     * @param InternalObjectOwnerEvent $event
     */
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

    /**
     * @param InternalObjectRouteEvent $event
     */
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
}
