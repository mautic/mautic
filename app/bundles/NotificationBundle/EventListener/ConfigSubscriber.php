<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\EventListener;

use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\NotificationBundle\Form\Type\NotificationConfigType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ConfigSubscriber
{
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: ConfigBuilderEvent::class)]
    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $event->addForm([
            'bundle'     => 'NotificationBundle',
            'formAlias'  => 'notification_config',
            'formType'   => NotificationConfigType::class,
            'formTheme'  => '@MauticNotification/FormTheme/Config/_config_notification_config_widget.html.twig',
            'parameters' => $event->getParametersFromConfig('MauticNotificationBundle'),
        ]);
    }
}
