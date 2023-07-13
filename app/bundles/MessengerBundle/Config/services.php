<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Messenger\Handler\MessageSubscriberInterface;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;
use Symfony\Component\Messenger\Transport\TransportFactory;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('Mautic\\MessengerBundle\\', '../')
        ->exclude('../{Config,Tests,Message}');

    // this config only applies to the services created by this file
    $services
        ->instanceof(MessageSubscriberInterface::class)
        // services whose classes are instances of CustomInterface will be tagged automatically
        ->tag('messenger.message_handler', ['bus' => 'messenger.bus.hit'])
    ;

    $services->alias(TransportFactory::class, 'messenger.transport_factory');
    $services->alias(SendersLocatorInterface::class, 'messenger.senders_locator');
};
