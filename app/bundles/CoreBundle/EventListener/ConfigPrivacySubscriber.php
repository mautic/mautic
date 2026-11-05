<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\CoreBundle\Form\Type\ConfigPrivacyType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ConfigPrivacySubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $event->addForm(
            [
                'bundle'     => 'CoreBundle',
                'formAlias'  => 'privacyconfig',
                'formType'   => ConfigPrivacyType::class,
                'formTheme'  => '@MauticCore/FormTheme/Config/_config_privacyconfig_widget.html.twig',
                'parameters' => [
                    'gravatar_enabled' => $event->getParametersFromConfig('MauticCoreBundle')['gravatar_enabled'] ?? false,
                ],
            ]
        );
    }
}
