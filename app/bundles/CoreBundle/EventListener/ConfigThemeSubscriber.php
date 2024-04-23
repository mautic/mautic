<?php

namespace Mautic\CoreBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\CoreBundle\Form\Type\ConfigThemeType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigThemeSubscriber implements EventSubscriberInterface
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
                'formAlias'  => 'themeconfig',
                'formType'   => ConfigThemeType::class,
                'formTheme'  => '@MauticCore/FormTheme/Config/_config_themeconfig_widget.html.twig',
                'parameters' => [
                    'theme'                           => $event->getParametersFromConfig('MauticCoreBundle')['theme'],
                    'theme_import_allowed_extensions' => $event->getParametersFromConfig('MauticCoreBundle')['theme_import_allowed_extensions'],
                ],
            ]
        );
    }
}
