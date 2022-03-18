<?php

namespace Mautic\PluginBundle\EventListener;

use Mautic\PluginBundle\Form\Type\IntegrationsListType;
use Mautic\PluginBundle\Helper\EventHelper;
use Mautic\PointBundle\Event\TriggerBuilderEvent;
use Mautic\PointBundle\PointEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PointSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PointEvents::TRIGGER_ON_BUILD => ['onTriggerBuild', 0],
        ];
    }

    public function onTriggerBuild(TriggerBuilderEvent $event)
    {
        $action = [
            'group'     => 'mautic.plugin.point.action',
            'label'     => 'mautic.plugin.actions.push_lead',
            'formType'  => IntegrationsListType::class,
            'formTheme' => 'MauticPluginBundle:FormTheme\Integration',
            'callback'  => [EventHelper::class, 'pushLead'],
        ];

        $event->addEvent('plugin.leadpush', $action);
    }
}
