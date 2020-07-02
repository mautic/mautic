<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\EventListener;

use Mautic\ApiBundle\Form\Type\ConfigType;
use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
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
            'bundle'     => 'ApiBundle',
            'formAlias'  => 'apiconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => 'MauticApiBundle:FormTheme\Config',
            'parameters' => $event->getParametersFromConfig('MauticApiBundle'),
        ]);
    }

    public function onConfigSave(ConfigEvent $event)
    {
        // Symfony craps out with integer for firewall settings
        $data                          = $event->getConfig('apiconfig');
        $data['api_enable_basic_auth'] = (bool) $data['api_enable_basic_auth'];
        $event->setConfig($data, 'apiconfig');
    }
}
