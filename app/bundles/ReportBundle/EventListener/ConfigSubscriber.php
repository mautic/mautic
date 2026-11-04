<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\EventListener;

use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\ReportBundle\Form\Type\ConfigType;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final class ConfigSubscriber
{
    #[AsEventListener(
        event: ConfigBuilderEvent::class,
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
