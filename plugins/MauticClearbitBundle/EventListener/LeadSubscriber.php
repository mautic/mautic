<?php

namespace MauticPlugin\MauticClearbitBundle\EventListener;

use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\MauticClearbitBundle\Helper\LookupHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LeadSubscriber implements EventSubscriberInterface
{
<<<<<<< HEAD
    public function __construct(private LookupHelper $lookupHelper)
=======
    private \MauticPlugin\MauticClearbitBundle\Helper\LookupHelper $lookupHelper;

    public function __construct(LookupHelper $lookupHelper)
>>>>>>> 11b4805f88 ([type-declarations] Re-run rector rules on plugins, Report, Sms, User, Lead, Dynamic, Config bundles)
    {
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
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
