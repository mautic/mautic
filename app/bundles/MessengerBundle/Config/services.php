<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Messenger\Transport\TransportFactory;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services
        ->load('Mautic\\MessengerBundle\\', '../')
        ->exclude('../{Config,Tests,Message}');

    $services->alias(TransportFactory::class, 'messenger.transport_factory');
};
