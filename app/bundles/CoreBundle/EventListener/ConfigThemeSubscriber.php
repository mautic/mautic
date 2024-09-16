<?php

namespace Mautic\CoreBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\CoreBundle\Form\Type\ConfigThemeType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigThemeSubscriber implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event)
    {
        $event->addForm(
            [
                'bundle'     => 'CoreBundle',
                'formAlias'  => 'themeconfig',
                'formType'   => ConfigThemeType::class,
                'formTheme'  => 'MauticCoreBundle:FormTheme\Config',
                'parameters' => [
                    'theme'                           => $event->getParametersFromConfig('MauticCoreBundle')['theme'],
                    'theme_import_allowed_extensions' => $event->getParametersFromConfig('MauticCoreBundle')['theme_import_allowed_extensions'],
                ],
            ]
        );
    }
}
