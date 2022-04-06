<?php

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\Form\Type\ConfigType;
use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
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
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event)
    {
        $event->addForm([
            'bundle'     => 'AssetBundle',
            'formAlias'  => 'assetconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => 'MauticAssetBundle:FormTheme\Config',
            'parameters' => $event->getParametersFromConfig('MauticAssetBundle'),
        ]);
    }
}
