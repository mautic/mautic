<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFullContactBundle\EventListener;

use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
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

    #[AsEventListener]
    public function companyPostSave(EntityPostSave $event): void
    {
        $company = $event->getEntity();
        if (!$company instanceof Company) {
            return;
        }

        $this->lookupHelper->lookupCompany($company, true, true);
    }
}
