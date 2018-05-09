<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\FCMNotificationBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\EventListener\CommonStatsSubscriber;

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
        $integration = $this->integrationHelper->getIntegrationObject('FCM');
        if (!$integration || $integration->getIntegrationSettings()->getIsPublished() === false) {
            return;
        }

        $this->addContactRestrictedRepositories($em, 'MauticNotificationBundle:Stat');
    }
}
