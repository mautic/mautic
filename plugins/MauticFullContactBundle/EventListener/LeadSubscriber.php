<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFullContactBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\MauticFullContactBundle\Helper\LookupHelper;

class LeadSubscriber extends CommonSubscriber
{
    /**
     * @var LookupHelper
     */
    protected $lookupHelper;

    /**
     * LeadSubscriber constructor.
     *
     * @param LookupHelper $lookupHelper
     */
    public function __construct(LookupHelper $lookupHelper)
    {
        $this->lookupHelper = $lookupHelper;
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

    /**
     * @param LeadEvent $event
     */
    public function leadPostSave(LeadEvent $event)
    {
        $this->lookupHelper->lookupContact($event->getLead(), true, true);
    }

    /**
     * @param CompanyEvent $event
     */
    public function companyPostSave(CompanyEvent $event)
    {
        $this->lookupHelper->lookupCompany($event->getCompany(), true, true);
    }
}
