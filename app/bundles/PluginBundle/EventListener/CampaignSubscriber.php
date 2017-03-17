<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\PluginEvents;

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
            CampaignEvents::CAMPAIGN_ON_BUILD        => ['onCampaignBuild', 0],
            PluginEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerAction', 0],
        ];
    }

    /**
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        $action = [
            'label'       => 'mautic.plugin.actions.push_lead',
            'description' => 'mautic.plugin.actions.tooltip',
            'formType'    => 'integration_list',
            'formTheme'   => 'MauticPluginBundle:FormTheme\Integration',
            'eventName'   => PluginEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];

        $event->addAction('plugin.leadpush', $action);

        $action = [
            'label'       => 'mautic.plugin.actions.push_lead_to_integration_campaign',
            'description' => 'mautic.plugin.actions.push_lead_to_integration_campaign.tooltip',
            'formType'    => 'integration_campaign_list',
            'formTheme'   => 'MauticPluginBundle:FormTheme\Integration',
            'eventName'   => PluginEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];

        $event->addAction('plugin.leadpushtocampaign', $action);
    }

    /**
     * @param CampaignExecutionEvent $event
     */
    public function onCampaignTriggerAction(CampaignExecutionEvent $event)
    {
        $config = $event->getConfig();
        $lead   = $event->getLead();

        $integration         = (!empty($config['integration'])) ? $config['integration'] : null;
        $integrationCampaign = (!empty($config['integration_campaign'])) ? $config['integration_campaign'] : null;
        $feature             = (empty($integration) || empty($integrationCampaign)) ? 'push_lead' : 'push_to_campaign';

        $services = $this->integrationHelper->getIntegrationObjects($integration, $feature);
        $success  = false;

        foreach ($services as $name => $s) {
            $settings = $s->getIntegrationSettings();
            if (!$settings->isPublished()) {
                continue;
            }

            if (method_exists($s, 'pushLead') && $feature == 'push_lead') {
                if ($s->pushLead($lead, $config)) {
                    $success = true;
                }
            }
            if (method_exists($s, 'pushLeadToCampaign') && $feature == 'push_to_campaign') {
                if ($s->pushLeadToCampaign($lead, $config)) {
                    $success = true;
                }
            }
        }

        return $event->setResult($success);
    }
}
