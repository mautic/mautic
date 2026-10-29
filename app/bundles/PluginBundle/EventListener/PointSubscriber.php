<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\EventListener;

use Mautic\PluginBundle\Form\Type\IntegrationsListType;
use Mautic\PluginBundle\Helper\EventHelper;
use Mautic\PointBundle\Event\TriggerBuilderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class PointSubscriber
{
    public function __construct(
        private EventHelper $eventHelper,
    ) {
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: TriggerBuilderEvent::class)]
    public function onTriggerBuild(TriggerBuilderEvent $event): void
    {
        $action = [
            'group'     => 'mautic.plugin.point.action',
            'label'     => 'mautic.plugin.actions.push_lead',
            'formType'  => IntegrationsListType::class,
            // 'formTheme' => 'MauticPluginBundle:FormTheme:Integration',
            'callback'  => $this->eventHelper->pushLead(...),
        ];

        $event->addEvent('plugin.leadpush', $action);
    }
}
