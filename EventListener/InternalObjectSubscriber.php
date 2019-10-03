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
use MauticPlugin\IntegrationsBundle\Event\InternalObjectFindByIdsEvent;
use MauticPlugin\IntegrationsBundle\Event\InternalObjectUpdateEvent;
use MauticPlugin\IntegrationsBundle\IntegrationEvents;
use MauticPlugin\IntegrationsBundle\Internal\Object\Company;
use MauticPlugin\IntegrationsBundle\Internal\Object\Contact;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\CompanyObjectHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\ContactObjectHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InternalObjectSubscriber implements EventSubscriberInterface
{
    /**
     * @var ContactObjectHelper
     */
    private $contactObjectHelper;

    /**
     * @var CompanyObjectHelper
     */
    private $companyObjectHelper;

    /**
     * @param ContactObjectHelper $contactObjectHelper
     * @param CompanyObjectHelper $companyObjectHelper
     */
    public function __construct(
        ContactObjectHelper $contactObjectHelper,
        CompanyObjectHelper $companyObjectHelper
    ) {
        $this->contactObjectHelper = $contactObjectHelper;
        $this->companyObjectHelper = $companyObjectHelper;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            IntegrationEvents::INTEGRATION_COLLECT_INTERNAL_OBJECTS => ['collectInternalObjects', 0],
            IntegrationEvents::INTEGRATION_UPDATE_INTERNAL_OBJECTS  => [
                ['updateContacts', 0],
                ['updateCompanies', 0],
            ],
            IntegrationEvents::INTEGRATION_CREATE_INTERNAL_OBJECTS => [
                ['createContacts', 0],
                ['createCompanies', 0],
            ],
            IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORDS_BY_ID => [
                ['findContactsByIds', 0],
                ['findCompaniesByIds', 0],
            ],
        ];
    }

    /**
     * @param InternalObjectEvent $event
     */
    public function collectInternalObjects(InternalObjectEvent $event): void
    {
        $event->addObject(new Contact());
        $event->addObject(new Company());
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
     * @param InternalObjectUpdateEvent $event
     */
    public function updateCompanies(InternalObjectUpdateEvent $event): void
    {
        if (Company::NAME !== $event->getObject()->getName()) {
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
     * @param InternalObjectCreateEvent $event
     */
    public function createCompanies(InternalObjectCreateEvent $event): void
    {
        if (Contact::NAME !== $event->getObject()->getName()) {
            return;
        }

        $event->setObjectMappings($this->companyObjectHelper->create($event->getCreateObjects()));
        $event->stopPropagation();
    }

    /**
     * @param InternalObjectFindByIdsEvent $event
     */
    public function findContactsByIds(InternalObjectFindByIdsEvent $event): void
    {
        if (Contact::NAME !== $event->getObject()->getName()) {
            return;
        }

        $event->setFoundObjects($this->contactObjectHelper->findObjectsByIds($event->getIds()));
        $event->stopPropagation();
    }

    /**
     * @param InternalObjectFindByIdsEvent $event
     */
    public function findCompaniesByIds(InternalObjectFindByIdsEvent $event): void
    {
        if (Company::NAME !== $event->getObject()->getName()) {
            return;
        }

        $event->setFoundObjects($this->companyObjectHelper->findObjectsByIds($event->getIds()));
        $event->stopPropagation();
    }
}
