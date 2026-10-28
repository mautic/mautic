<?php

namespace MauticPlugin\MauticFullContactBundle\EventListener;

use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\CompanyPostSaveEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\LeadPostSaveEvent;
use MauticPlugin\MauticFullContactBundle\Helper\LookupHelper;

final readonly class LeadSubscriber
{
    public function __construct(
        private LookupHelper $lookupHelper,
    ) {
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: LeadPostSaveEvent::class,
        priority: 0
    )]
    public function leadPostSave(LeadEvent $event): void
    {
        $this->lookupHelper->lookupContact($event->getLead(), true, true);
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: CompanyPostSaveEvent::class,
        priority: 0
    )]
    public function companyPostSave(CompanyEvent $event): void
    {
        $this->lookupHelper->lookupCompany($event->getCompany(), true, true);
    }
}
