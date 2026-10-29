<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\EventListener;

use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\MessengerBundle\Form\Type\ConfigType;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class ConfigSubscriber
{
    #[AsEventListener(
        event: ConfigBuilderEvent::class,
    )]
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
