<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('Mautic\\MessengerBundle\\', '../')
        ->exclude('../{'.implode(',', [
                'Config',
                'Tests',
            ]).'}');

    $services->alias(\Symfony\Component\Messenger\Transport\TransportFactory::class, 'messenger.transport_factory');
};
