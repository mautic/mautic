<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;

/**
 * Class ConfigSubscriber.
 */
class ConfigThemeSubscriber extends CommonSubscriber
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
        $event->addForm([
            'bundle'     => 'CoreBundle',
            'formAlias'  => 'themeconfig',
            'formTheme'  => 'MauticCoreBundle:FormTheme\Config',
            'parameters' => [
                    'theme'                           => $event->getParametersFromConfig('MauticCoreBundle')['theme'],
                    'theme_import_allowed_extensions' => $event->getParametersFromConfig('MauticCoreBundle')['theme_import_allowed_extensions'],
                ],
        ]);
    }
}
