<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Mautic\EmailBundle\Model\AbTest\EmailVariantConverterService;
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
    ];

    $services->load('Mautic\\EmailBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\EmailBundle\\Entity\\', '../Entity/*Repository.php');

    $services->alias(\Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface::class, \Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProvider::class);
    $services->alias('mautic.email.variant.converter', EmailVariantConverterService::class);
    $services->set(\Mautic\EmailBundle\Mailer\Transport\TransportFactory::class)
        ->decorate('mailer.transport_factory');
};
