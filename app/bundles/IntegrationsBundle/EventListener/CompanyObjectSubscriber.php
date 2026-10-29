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
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\CompanyObjectHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;

final readonly class CompanyObjectSubscriber
{
    public function __construct(
        private CompanyObjectHelper $companyObjectHelper,
        private RouterInterface $router,
    ) {
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: InternalObjectEvent::class)]
    public function collectInternalObjects(InternalObjectEvent $event): void
    {
        $event->addObject(new Company());
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: InternalObjectUpdateEvent::class)]
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

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: InternalObjectCreateEvent::class)]
    public function createCompanies(InternalObjectCreateEvent $event): void
    {
        if (Company::NAME !== $event->getObject()->getName()) {
            return;
        }

        $event->setObjectMappings($this->companyObjectHelper->create($event->getCreateObjects()));
        $event->stopPropagation();
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: InternalObjectFindEvent::class)]
    public function findCompaniesByIds(InternalObjectFindEvent $event): void
    {
        if (Company::NAME !== $event->getObject()->getName() || empty($event->getIds())) {
            return;
        }

        $event->setFoundObjects($this->companyObjectHelper->findObjectsByIds($event->getIds()));
        $event->stopPropagation();
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: InternalObjectFindEvent::class)]
    public function findCompaniesByDateRange(InternalObjectFindEvent $event): void
    {
        if (Company::NAME !== $event->getObject()->getName() || !$event->getDateRange() instanceof DateRange) {
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

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: InternalObjectFindEvent::class)]
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

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: InternalObjectOwnerEvent::class)]
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

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: InternalObjectRouteEvent::class)]
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

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: InternalObjectFindByIdEvent::class)]
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
