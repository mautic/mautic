<?php

namespace MauticPlugin\MauticSocialBundle\EventListener;

use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\ConfigBundle\Event\ConfigPreSaveEvent;
use MauticPlugin\MauticSocialBundle\Form\Type\ConfigType;
use MauticPlugin\MauticSocialBundle\Integration\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class ConfigSubscriber
{
    public function __construct(
        private Config $config,
    ) {
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: ConfigBuilderEvent::class)]
    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        if (!$this->config->isPublished()) {
            return;
        }
        $event->addForm(
            [
                'formAlias'  => 'social_config',
                'formTheme'  => '@MauticSocial/FormTheme/Config/_config_social_config_widget.html.twig',
                'formType'   => ConfigType::class,
                'parameters' => $event->getParametersFromConfig('MauticSocialBundle'),
            ]
        );
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: ConfigPreSaveEvent::class)]
    public function onConfigSave(ConfigEvent $event): void
    {
        $values = $event->getConfig();

        // Manipulate the values
        if (!empty($values['social_config']['twitter_handle_field'])) {
            $values['social_config']['twitter_handle_field'] = htmlspecialchars($values['social_config']['twitter_handle_field']);
        }

        // Set updated values
        $event->setConfig($values);
    }
}
