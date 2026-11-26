<?php

namespace MauticPlugin\MauticFullContactBundle\EventListener;

use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\CompanyPostSaveEvent;
use MauticPlugin\MauticFullContactBundle\Helper\LookupHelper;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class LeadSubscriber
{
    public function __construct(
        private LookupHelper $lookupHelper,
    ) {
    }

    #[AsEventListener]
    public function leadPostSave(EntityPostSave $event): void
    {
        $lead = $event->getEntity();
        if (!$lead instanceof Lead) {
            return;
        }

        $this->lookupHelper->lookupContact($lead, true, true);
    }

    #[AsEventListener(
        event: CompanyPostSaveEvent::class,
    )]
    public function companyPostSave(CompanyEvent $event): void
    {
        $this->lookupHelper->lookupCompany($event->getCompany(), true, true);
    }
}
