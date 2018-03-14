<?php

namespace Mautic\CampaignBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;

/**
 * Class ConfigSubscriber.
 */
class ConfigSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
            ConfigEvents::CONFIG_PRE_SAVE    => ['onConfigSave', 0],
        ];
    }

    /**
     * @param ConfigBuilderEvent $event
     */
    public function onConfigGenerate(ConfigBuilderEvent $event)
    {
        $event->addForm(
            [
                'bundle'     => 'CampaignBundle',
                'formAlias'  => 'campaignconfig',
                'formTheme'  => 'MauticCampaignBundle:FormTheme\Config',
                'parameters' => $event->getParametersFromConfig('MauticCampaignBundle'),
            ]
        );
    }

    /**
     * @param ConfigEvent $event
     */
    public function onConfigSave(ConfigEvent $event)
    {
        /** @var array $values */
        $values = $event->getConfig();

        // Manipulate the values
        if (!empty($values['campaignconfig']['campaign_time_wait_on_event_false'])) {
            $values['campaignconfig']['campaign_time_wait_on_event_false'] = htmlspecialchars($values['campaignconfig']['campaign_time_wait_on_event_false']);
        }
        if (!empty($values['campaignconfig']['campaign_default_for_template'])) {
            $values['campaignconfig']['campaign_default_for_template'] = htmlspecialchars($values['campaignconfig']['campaign_default_for_template']);
        } else {
            // dont allow a force default if the campaign default is null
            $values['campaignconfig']['campaign_force_default'] = 0;
        }
        $values['campaignconfig']['campaign_force_default'] = htmlspecialchars($values['campaignconfig']['campaign_force_default']);

        // Set updated values
        $event->setConfig($values);
    }
}
