<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\MessengerBundle\Form\Type\ConfigType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @return array<string, array<int, int|string>>
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $event->addForm([
            'bundle'     => 'MessengerBundle',
            'formAlias'  => 'messengerconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => '@MauticMessenger/FormTheme/Config/_config_messengerconfig_widget.html.twig',
            'parameters' => $event->getParametersFromConfig('MauticMessengerBundle'),
        ]);
    }
}
