<?php

namespace MauticPlugin\MauticFullContactBundle\EventListener;

use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\MauticFullContactBundle\Helper\LookupHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LeadSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LookupHelper $lookupHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeadEvents::LEAD_POST_SAVE    => ['leadPostSave', 0],
            LeadEvents::COMPANY_POST_SAVE => ['companyPostSave', 0],
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
