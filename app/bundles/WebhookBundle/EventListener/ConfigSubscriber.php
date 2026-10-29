<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\EventListener;

use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\WebhookBundle\Form\Type\ConfigType;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class ConfigSubscriber
{
    #[AsEventListener(
        event: ConfigBuilderEvent::class,
        priority: 0
    )]
    public function onConfigGenerate(ConfigBuilderEvent $event): void
    {
        $event->addForm([
            'bundle'     => 'WebhookBundle',
            'formAlias'  => 'webhookconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => '@MauticWebhook/FormTheme/Config/_config_webhookconfig_widget.html.twig',
            'parameters' => $event->getParametersFromConfig('MauticWebhookBundle'),
        ]);
    }
}
