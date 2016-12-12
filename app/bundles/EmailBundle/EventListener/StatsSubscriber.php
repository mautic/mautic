<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

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
        $this->repositories[] = $em->getRepository('MauticEmailBundle:StatDevice');
        $this->repositories[] = $em->getRepository('MauticEmailBundle:Stat');

        // Exclude copy and tokens as these can be quite large
        $this->selects[$em->getRepository('MauticEmailBundle:Stat')->getTableName()] =
            [
                'id',
                'email_id',
                'lead_id',
                'list_id',
                'ip_id',
                'email_address',
                'date_sent',
                'is_read',
                'is_failed',
                'viewed_in_browser',
                'date_read',
                'tracking_hash',
                'retry_count',
                'source',
                'source_id',
                'open_count',
                'last_opened',
                'open_details',
            ];
    }
}
