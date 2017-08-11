<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;

/**
 * Class ConfigSubscriber.
 */
class ConfigSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
            ConfigEvents::CONFIG_PRE_SAVE    => ['onConfigSave', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event)
    {
        $event->addForm([
            'bundle'     => 'PageBundle',
            'formAlias'  => 'trackingconfig',
            'formTheme'  => 'MauticPageBundle:FormTheme\Config',
            'parameters' => $event->getParametersFromConfig('MauticPageBundle'),
        ]);

        $event->addForm([
            'bundle'     => 'PageBundle',
            'formAlias'  => 'pageconfig',
            'formTheme'  => 'MauticPageBundle:FormTheme\Config',
            'parameters' => $event->getParametersFromConfig('MauticPageBundle'),
        ]);
    }

    public function onConfigSave(ConfigEvent $event)
    {
        $values = $event->getConfig();

        if (!empty($values['pageconfig']['google_analytics'])) {
            $values['pageconfig']['google_analytics'] = htmlspecialchars($values['pageconfig']['google_analytics']);
            $event->setConfig($values);
        }
    }
}
