<?php

declare(strict_types=1);

namespace Mautic\FormBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\ConfigBundle\Event\ConfigBuilderEvent;
use Mautic\FormBundle\Form\Type\ConfigFormType;

final class ConfigSubscriber
{
    #[AsEventListener(event: ConfigBuilderEvent::class)]
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
