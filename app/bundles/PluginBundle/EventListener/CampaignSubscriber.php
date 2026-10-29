<?php

namespace Mautic\PluginBundle\EventListener;

use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\PluginBundle\Form\Type\IntegrationsListType;
use Mautic\PluginBundle\PluginEvents;

final class CampaignSubscriber
{
    use PushToIntegrationTrait;
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: CampaignBuilderEvent::class,
        priority: 0
    )]
    public function onCampaignBuild(CampaignBuilderEvent $event): void
    {
        $action = [
            'label'       => 'mautic.plugin.actions.push_lead',
            'description' => 'mautic.plugin.actions.tooltip',
            'formType'    => IntegrationsListType::class,
            'formTheme'      => '@MauticPlugin/FormTheme/Integration/layout.html.twig',
            'batchEventName' => PluginEvents::ON_CAMPAIGN_BATCH_ACTION,
        ];

        $event->addAction('plugin.leadpush', $action);
    }
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: PluginEvents::ON_CAMPAIGN_BATCH_ACTION,
        priority: 0
    )]
    public function onCampaignTriggerAction(PendingEvent $event): void
    {
        $campaignEvent = $event->getEvent();

        foreach ($event->getPending() as $log) {
            $config                  = $campaignEvent->getProperties();
            $config['campaignEvent'] = $campaignEvent;
            $config['leadEventLog']  = $log;
            $lead                    = $log->getLead();
            $errors                  = [];
            $success                 = $this->pushToIntegration($config, $lead, $errors);

            if ($success) {
                $event->pass($log);

                continue;
            }

            $event->fail($log, [] !== $errors ? implode('<br />', $errors) : 'Integration push failed.');
        }
    }
}
