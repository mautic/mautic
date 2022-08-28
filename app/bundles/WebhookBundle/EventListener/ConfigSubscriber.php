<?php

namespace Mautic\WebhookBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\WebhookBundle\Form\Type\ConfigType;
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
            'bundle'     => 'WebhookBundle',
            'formAlias'  => 'webhookconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => 'MauticWebhookBundle:FormTheme\Config',
            'parameters' => $event->getParametersFromConfig('MauticWebhookBundle'),
        ]);
    }
}
