<?php

namespace Mautic\PluginBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\PluginBundle\Form\Type\IntegrationsListType;
use Mautic\PluginBundle\PluginEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignSubscriber implements EventSubscriberInterface
{
    use PushToIntegrationTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD        => ['onCampaignBuild', 0],
            PluginEvents::ON_CAMPAIGN_TRIGGER_ACTION => ['onCampaignTriggerAction', 0],
        ];
    }

    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $action = [
            'label'       => 'mautic.plugin.actions.push_lead',
            'description' => 'mautic.plugin.actions.tooltip',
            'formType'    => IntegrationsListType::class,
            'formTheme'   => '@MauticPlugin/FormTheme/Integration/layout.html.twig',
            'eventName'   => PluginEvents::ON_CAMPAIGN_TRIGGER_ACTION,
        ];

        $event->addAction('plugin.leadpush', $action);
    }

    public function onCampaignTriggerAction(CampaignExecutionEvent $event): void
    {
        $config                  = $event->getConfig();
        $config['campaignEvent'] = $event->getEvent();
        $config['leadEventLog']  = $event->getLogEntry();
        $lead                    = $event->getLead();
        $errors                  = [];
        $success                 = $this->pushToIntegration($config, $lead, $errors);

        if (count($errors)) {
            $log = $event->getLogEntry();
            $log->appendToMetadata(
                [
                    'failed' => 1,
                    'reason' => implode('<br />', $errors),
                ]
            );
        }

        $event->setResult($success);
    }
}
