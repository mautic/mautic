<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\Form\Type\ConfigType;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class ConfigSubscriber
{
    #[AsEventListener(
        event: ConfigBuilderEvent::class,
    )]
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
