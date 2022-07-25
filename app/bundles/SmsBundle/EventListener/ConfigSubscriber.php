<?php

namespace Mautic\SmsBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\SmsBundle\Form\Type\ConfigType;
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
            'bundle'     => 'SmsBundle',
            'formAlias'  => 'smsconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => 'MauticSmsBundle:FormTheme\Config',
            'parameters' => $event->getParametersFromConfig('MauticSmsBundle'),
        ]);
    }
}
