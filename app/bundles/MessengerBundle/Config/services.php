<?php

declare(strict_types=1);

use Mautic\MessengerBundle\Serializer\MauticMessengerSerializer;
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

    $services
        ->instanceof(MessageSubscriberInterface::class)
        // services whose classes are instances of CustomInterface will be tagged automatically
        ->tag('messenger.message_handler', ['bus' => 'messenger.bus.hit']);

    $services->set('messenger.transport.jms_serializer', MauticMessengerSerializer::class);

    $services->alias(TransportFactory::class, 'messenger.transport_factory');
    $services->alias(SendersLocatorInterface::class, 'messenger.senders_locator');
};
