<?php
namespace Mautic\CampaignBundle\EventListener;

use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;

/**
 * Class ConfigSubscriber
 */
class ConfigSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            ConfigEvents::CONFIG_ON_GENERATE => array('onConfigGenerate', 0),
            ConfigEvents::CONFIG_PRE_SAVE    => array('onConfigSave', 0)
        );
    }

    /**
     * @param ConfigBuilderEvent $event
     */
    public function onConfigGenerate(ConfigBuilderEvent $event)
    {
        $event->addForm(
            array(
                'bundle'     => 'CampaignBundle',
                'formAlias'  => 'campaignconfig',
                'formTheme'  => 'MauticCampaignBundle:FormTheme\Config',
                'parameters' => $event->getParametersFromConfig('MauticCampaignBundle')
            )
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

        // Set updated values
        $event->setConfig($values);
    }
}