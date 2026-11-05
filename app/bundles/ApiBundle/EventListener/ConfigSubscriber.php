<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\EventListener;

use Mautic\ApiBundle\Form\Type\ConfigType;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\ConfigBundle\Event\ConfigPreSaveEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class ConfigSubscriber
{
    #[AsEventListener(
        event: ConfigBuilderEvent::class,
    )]
    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $event->addForm([
            'bundle'     => 'ApiBundle',
            'formAlias'  => 'apiconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => '@MauticApi/FormTheme/Config/_config_apiconfig_widget.html.twig',
            'parameters' => $event->getParametersFromConfig('MauticApiBundle'),
        ]);
    }

    #[AsEventListener(
        event: ConfigPreSaveEvent::class,
    )]
    public function onConfigSave(ConfigEvent $event): void
    {
        // Symfony craps out with integer for firewall settings
        $data = $event->getConfig('apiconfig');
        if (isset($data['api_enable_basic_auth'])) {
            $data['api_enable_basic_auth'] = (bool) $data['api_enable_basic_auth'];
            $event->setConfig($data, 'apiconfig');
        }
    }
}
