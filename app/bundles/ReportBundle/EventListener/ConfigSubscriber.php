<?php

namespace Mautic\ReportBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ReportBundle\Form\Type\ConfigType;
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
            'bundle'     => 'ReportBundle',
            'formAlias'  => 'reportconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => 'MauticReportBundle:FormTheme\Config',
            'parameters' => $event->getParametersFromConfig('MauticReportBundle'),
        ]);
    }
}
