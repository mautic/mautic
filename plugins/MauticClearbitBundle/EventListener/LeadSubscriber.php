<?php

namespace MauticPlugin\MauticClearbitBundle\EventListener;

use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\CompanyPostSaveEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\LeadPostSaveEvent;
use MauticPlugin\MauticClearbitBundle\Helper\LookupHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class LeadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LookupHelper $lookupHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadPostSaveEvent::class    => ['leadPostSave', 0],
            CompanyPostSaveEvent::class => ['companyPostSave', 0],
        ];
    }

    public function leadPostSave(LeadEvent $event): void
    {
        $this->lookupHelper->lookupContact($event->getLead(), true, true);
    }

    public function companyPostSave(CompanyEvent $event): void
    {
        $this->lookupHelper->lookupCompany($event->getCompany(), true, true);
    }
}
