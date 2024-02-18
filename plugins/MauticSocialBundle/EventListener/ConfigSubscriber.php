<?php

namespace MauticPlugin\MauticSocialBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ConfigBundle\Event\ConfigEvent;
use MauticPlugin\MauticSocialBundle\Form\Type\ConfigType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::CONFIG_ON_GENERATE => ['onConfigGenerate', 0],
            ConfigEvents::CONFIG_PRE_SAVE    => ['onConfigSave', 0],
        ];
    }

    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $event->addForm(
            [
                'formAlias'  => 'social_config',
                'formTheme'  => '@MauticSocial/FormTheme/Config/_config_social_config_widget.html.twig',
                'formType'   => ConfigType::class,
                'parameters' => $event->getParametersFromConfig('MauticSocialBundle'),
            ]
        );
    }

    public function onConfigSave(ConfigEvent $event): void
    {
        /** @var array $values */
        $values = $event->getConfig();

        // Manipulate the values
        if (!empty($values['social_config']['twitter_handle_field'])) {
            $values['social_config']['twitter_handle_field'] = htmlspecialchars($values['social_config']['twitter_handle_field']);
        }

        // Set updated values
        $event->setConfig($values);
    }
}
