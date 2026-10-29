<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\FormBundle\Form\Type\ConfigFormType;
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
            'bundle'     => 'FormBundle',
            'formAlias'  => 'formconfig',
            'formType'   => ConfigFormType::class,
            'formTheme'  => '@MauticForm/FormTheme/Config/_config_formconfig_widget.html.twig',
            'parameters' => $event->getParametersFromConfig('MauticFormBundle'),
        ]);
    }
}
