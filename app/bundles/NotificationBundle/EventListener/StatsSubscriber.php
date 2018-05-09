<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\NotificationBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\EventListener\CommonStatsSubscriber;
use Mautic\PluginBundle\Helper\IntegrationHelper;

/**
 * Class StatsSubscriber.
 */
class StatsSubscriber extends CommonStatsSubscriber
{
    /**
     * @var integrationHelper
     */
    protected $integrationHelper;

    /**
     * StatsSubscriber constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em, integrationHelper $integrationHelper)
    {
        $this->integrationHelper = $integrationHelper;

        $integration = $this->integrationHelper->getIntegrationObject('OneSignal');
        if (!$integration || $integration->getIntegrationSettings()->getIsPublished() === false) {
            return;
        }

        $this->addContactRestrictedRepositories($em, 'MauticNotificationBundle:Stat');
    }
}
