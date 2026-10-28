<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\EventListener;

use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ReportBundle\Form\Type\ConfigType;

final class ConfigSubscriber
{
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(
        event: ConfigBuilderEvent::class,
        priority: 0
    )]
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
