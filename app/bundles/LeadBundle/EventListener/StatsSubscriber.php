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
        $this->repositories[] = $em->getRepository('MauticLeadBundle:CompanyLead');
        $this->repositories[] = $em->getRepository('MauticLeadBundle:LeadCategory');
        $this->repositories[] = $em->getRepository('MauticLeadBundle:LeadDevice');
        $this->repositories[] = $em->getRepository('MauticLeadBundle:LeadList');
        $this->repositories[] = $em->getRepository('MauticLeadBundle:DoNotContact');
        $this->repositories[] = $em->getRepository('MauticLeadBundle:FrequencyRule');
        $this->repositories[] = $em->getRepository('MauticLeadBundle:UtmTag');
    }
}
