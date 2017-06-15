<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\EventListener\CommonStatsSubscriber;

/**
 * Class StatsSubscriber.
 */
class StatsSubscriber extends CommonStatsSubscriber
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * StatsSubscriber constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->addContactRestrictedRepositories(
            $em,
            [
                'MauticLeadBundle:CompanyChangeLog',
                'MauticLeadBundle:PointsChangeLog',
                'MauticLeadBundle:StagesChangeLog',
                'MauticLeadBundle:CompanyLead',
                'MauticLeadBundle:LeadCategory',
                'MauticLeadBundle:LeadDevice',
                'MauticLeadBundle:ListLead',
                'MauticLeadBundle:DoNotContact',
                'MauticLeadBundle:FrequencyRule',
                'MauticLeadBundle:UtmTag',
            ]
        );
    }
}
