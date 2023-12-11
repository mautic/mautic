<?php

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\Form\Type\ConfigType;
use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
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
            'bundle'     => 'AssetBundle',
            'formAlias'  => 'assetconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => '@MauticAsset/FormTheme/Config/_config_assetconfig_widget.html.twig',
            'parameters' => $event->getParametersFromConfig('MauticAssetBundle'),
        ]);
    }
}
