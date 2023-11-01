<?php

namespace Mautic\FormBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\FormBundle\Form\Type\ConfigFormType;
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
            'bundle'     => 'FormBundle',
            'formAlias'  => 'formconfig',
            'formType'   => ConfigFormType::class,
            'formTheme'  => 'MauticFormBundle:FormTheme\Config',
            'parameters' => $event->getParametersFromConfig('MauticFormBundle'),
        ]);
    }
}
