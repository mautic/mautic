<?php

declare(strict_types=1);

namespace Mautic\PrivacyBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\PrivacyBundle\Form\Type\ConfigType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ConfigSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $event->addForm([
            'bundle'     => 'PrivacyBundle',
            'formAlias'  => 'privacyconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => '@MauticPrivacy/FormTheme/Config/_config_privacyconfig_widget.html.twig',
            'parameters' => $event->getParametersFromConfig('MauticPrivacyBundle'),
        ]);
    }
}
