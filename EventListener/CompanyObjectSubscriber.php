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
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\CompanyObjectHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Router;

class CompanyObjectSubscriber implements EventSubscriberInterface
{
    /**
     * @var CompanyObjectHelper
     */
    private $companyObjectHelper;

    /**
     * @var Router
     */
    private $router;

    /**
     * @param CompanyObjectHelper $companyObjectHelper
     * @param Router              $router
     */
    public function __construct(
        CompanyObjectHelper $companyObjectHelper,
        Router $router
    ) {
        $this->companyObjectHelper = $companyObjectHelper;
        $this->router              = $router;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            IntegrationEvents::INTEGRATION_COLLECT_INTERNAL_OBJECTS => ['collectInternalObjects', 0],
            IntegrationEvents::INTEGRATION_UPDATE_INTERNAL_OBJECTS  => ['updateCompanies', 0],
            IntegrationEvents::INTEGRATION_CREATE_INTERNAL_OBJECTS  => ['createCompanies', 0],
            IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORDS    => [
                ['findCompaniesByIds', 0],
                ['findCompaniesByDateRange', 0],
                ['findCompaniesByFieldValues', 0],
            ],
            IntegrationEvents::INTEGRATION_FIND_OWNER_IDS              => ['findOwnerIdsForCompanies', 0],
            IntegrationEvents::INTEGRATION_BUILD_INTERNAL_OBJECT_ROUTE => ['buildCompanyRoute', 0],
        ];
    }

    /**
     * @param InternalObjectEvent $event
     */
    public function collectInternalObjects(InternalObjectEvent $event): void
    {
        $event->addObject(new Company());
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
            $this->companyObjectHelper->update(
                $event->getIdentifiedObjectIds(),
                $event->getUpdateObjects()
            )
        );
        $event->stopPropagation();
    }

    /**
     * @param InternalObjectCreateEvent $event
     */
    public function createCompanies(InternalObjectCreateEvent $event): void
    {
        if (Company::NAME !== $event->getObject()->getName()) {
            return;
        }

        $event->setObjectMappings($this->companyObjectHelper->create($event->getCreateObjects()));
        $event->stopPropagation();
    }

    /**
     * @param InternalObjectFindEvent $event
     */
    public function findCompaniesByIds(InternalObjectFindEvent $event): void
    {
        if (Company::NAME !== $event->getObject()->getName() || empty($event->getIds())) {
            return;
        }

        $event->setFoundObjects($this->companyObjectHelper->findObjectsByIds($event->getIds()));
        $event->stopPropagation();
    }

    /**
     * @param InternalObjectFindEvent $event
     */
    public function findCompaniesByDateRange(InternalObjectFindEvent $event): void
    {
        if (Company::NAME !== $event->getObject()->getName() || empty($event->getDateRange())) {
            return;
        }

        $event->setFoundObjects(
            $this->companyObjectHelper->findObjectsBetweenDates(
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
    public function findCompaniesByFieldValues(InternalObjectFindEvent $event): void
    {
        if (Company::NAME !== $event->getObject()->getName() || empty($event->getFieldValues())) {
            return;
        }

        $event->setFoundObjects(
            $this->companyObjectHelper->findObjectsByFieldValues(
                $event->getFieldValues()
            )
        );
        $event->stopPropagation();
    }

    /**
     * @param InternalObjectOwnerEvent $event
     */
    public function findOwnerIdsForCompanies(InternalObjectOwnerEvent $event): void
    {
        if (Company::NAME !== $event->getObject()->getName()) {
            return;
        }

        $event->setOwners(
            $this->companyObjectHelper->findOwnerIds(
                $event->getObjectIds()
            )
        );
        $event->stopPropagation();
    }

    /**
     * @param InternalObjectRouteEvent $event
     */
    public function buildCompanyRoute(InternalObjectRouteEvent $event): void
    {
        if (Company::NAME !== $event->getObject()->getName()) {
            return;
        }

        $event->setRoute(
            $this->router->generate(
                'mautic_company_action',
                [
                    'objectAction' => 'view',
                    'objectId'     => $event->getId(),
                ]
            )
        );
        $event->stopPropagation();
    }
}
