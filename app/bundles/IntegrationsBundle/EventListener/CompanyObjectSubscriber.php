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
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\CompanyObjectHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;

class CompanyObjectSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CompanyObjectHelper $companyObjectHelper,
        private RouterInterface $router
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            IntegrationEvents::INTEGRATION_COLLECT_INTERNAL_OBJECTS    => ['collectInternalObjects', 0],
            IntegrationEvents::INTEGRATION_UPDATE_INTERNAL_OBJECTS     => ['updateCompanies', 0],
            IntegrationEvents::INTEGRATION_CREATE_INTERNAL_OBJECTS     => ['createCompanies', 0],
            IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORDS       => [
                ['findCompaniesByIds', 0],
                ['findCompaniesByDateRange', 0],
                ['findCompaniesByFieldValues', 0],
            ],
            IntegrationEvents::INTEGRATION_FIND_OWNER_IDS              => ['findOwnerIdsForCompanies', 0],
            IntegrationEvents::INTEGRATION_BUILD_INTERNAL_OBJECT_ROUTE => ['buildCompanyRoute', 0],
            IntegrationEvents::INTEGRATION_FIND_INTERNAL_RECORD        => ['findCompanyById', 0],
        ];
    }

    public function collectInternalObjects(InternalObjectEvent $event): void
    {
        $event->addObject(new Company());
    }

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

    public function createCompanies(InternalObjectCreateEvent $event): void
    {
        if (Company::NAME !== $event->getObject()->getName()) {
            return;
        }

        $event->setObjectMappings($this->companyObjectHelper->create($event->getCreateObjects()));
        $event->stopPropagation();
    }

    public function findCompaniesByIds(InternalObjectFindEvent $event): void
    {
        if (Company::NAME !== $event->getObject()->getName() || empty($event->getIds())) {
            return;
        }

        $event->setFoundObjects($this->companyObjectHelper->findObjectsByIds($event->getIds()));
        $event->stopPropagation();
    }

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

    public function findCompanyById(InternalObjectFindByIdEvent $event): void
    {
        if (null === $event->getId() || Company::NAME !== $event->getObject()->getName()) {
            return;
        }

        $company = $this->companyObjectHelper->findObjectById($event->getId());

        if (null === $company) {
            return;
        }

        $this->companyObjectHelper->setFieldValues($company);
        $event->setEntity($company);
    }
}
