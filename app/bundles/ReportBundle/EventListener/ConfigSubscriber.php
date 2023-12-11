<?php

namespace Mautic\ReportBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ReportBundle\Form\Type\ConfigType;
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
            'bundle'     => 'ReportBundle',
            'formAlias'  => 'reportconfig',
            'formType'   => ConfigType::class,
            'formTheme'  => '@MauticReport/FormTheme/Config/_config_reportconfig_widget.html.twig',
            'parameters' => $event->getParametersFromConfig('MauticReportBundle'),
        ]);
    }
}
