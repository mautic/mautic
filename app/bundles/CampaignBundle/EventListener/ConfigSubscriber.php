<?php

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\Form\Type\ConfigType;
use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
            ConfigEvents::CONFIG_PRE_SAVE    => ['onConfigSave', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $event->addForm(
            [
                'bundle'     => 'CampaignBundle',
                'formAlias'  => 'campaignconfig',
                'formType'   => ConfigType::class,
                'formTheme'  => '@MauticCampaign/FormTheme/Config/_config_campaignconfig_widget.html.twig',
                'parameters' => $event->getParametersFromConfig('MauticCampaignBundle'),
            ]
        );
    }

    public function onConfigSave(ConfigEvent $event): void
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
