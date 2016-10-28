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
use Mautic\CoreBundle\EventListener\StatsSubscriber as CommonStatsSubscriber;

/**
 * Class StatsSubscriber.
 */
class StatsSubscriber extends CommonStatsSubscriber
{
    /**
     * StatsSubscriber constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->repositories[] = $em->getRepository('MauticLeadBundle:CompanyChangeLog');
        $this->repositories[] = $em->getRepository('MauticLeadBundle:PointsChangeLog');
        $this->repositories[] = $em->getRepository('MauticLeadBundle:StagesChangeLog');
    }
}
