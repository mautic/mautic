<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      WebMecanik
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticWebinarBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticWebinarBundle\WebinarEvents;

/**
 * Class CampaignSubscriber.
 */
class CampaignSubscriber extends CommonSubscriber
{
    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;
    /**
     * CampaignSubscriber constructor.
     *
     * @param IntegrationHelper $integrationHelper
     */
    public function __construct(IntegrationHelper $integrationHelper)
    {
        $this->integrationHelper = $integrationHelper;
    }
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD => ['onCampaignBuild', 0],
            WebinarEvents::ON_WEBINAR_EVENT => ['onWebinarEvent', 0],
        ];
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $services = $this->integrationHelper->getIntegrationObjects();

        foreach ($services as $name => $s) {
            $settings = $s->getIntegrationSettings();
            if (!$settings->isPublished()) {
                continue;
            }
            //Add webinar condition has attended a webinar
            if (method_exists($s, 'hasAttendedWebinar')) {
                $condition = [
                    'label' => 'mautic.plugin.webinars.attended',
                    'description' => $s->getName(),
                    'formType' => $s->getName() . '_campaignevent_webinars',
                    'formTypeOptions' => ['integrationObject' => $s],
                    'eventName' => WebinarEvents::ON_WEBINAR_EVENT,
                ];

                $event->addCondition('mautic.plugin.webinar.' . $name, $condition);
            }
        }
    }



    /**
     * @param CampaignExecutionEvent $event
     *
     * @return bool
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function onWebinarEvent(CampaignExecutionEvent $event)
    {
        $config   = $event->getConfig();
        $lead = $event->getLead();
        $subscribed = false;
        if ($lead) {
            $services = $this->integrationHelper->getIntegrationObjects();
            foreach ($services as $name => $s) {
                $settings = $s->getIntegrationSettings();
                if (!$settings->isPublished() && !method_exists($s, 'getWebinarSubscription')) {
                    continue;
                }

                $subscribed = $s->hasAttendedWebinar($config['webinar'], $lead);
            }
        }

        return $subscribed;
    }
}