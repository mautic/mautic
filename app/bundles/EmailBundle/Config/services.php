<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'OptionsAccessor',
        'MonitoredEmail/Accessor',
        'MonitoredEmail/Organizer',
        'MonitoredEmail/Processor',
        'Stat/Reference.php',
        // Will be removed in M5:
        'Swiftmailer/Exception',
        'Swiftmailer/Momentum/Callback/ResponseItem.php',
        'Swiftmailer/Momentum/Callback/ResponseItems.php',
        'Swiftmailer/Momentum/DTO',
        'Swiftmailer/Momentum/Exception',
        'Swiftmailer/Momentum/Metadata',
        'Swiftmailer/SendGrid/Callback/ResponseItem.php',
        'Swiftmailer/SendGrid/Callback/ResponseItems.php',
    ];

    $services->load('Mautic\\EmailBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\EmailBundle\\Entity\\', '../Entity/*Repository.php');

    $services->alias(GeneratedColumnsProviderInterface::class, \Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProvider::class);
};
