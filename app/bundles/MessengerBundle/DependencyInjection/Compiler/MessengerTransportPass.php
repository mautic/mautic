<?php

namespace Mautic\MessengerBundle\DependencyInjection\Compiler;

use Mautic\MessengerBundle\Model\MessengerTransportType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class MessengerTransportPass.
 */
class MessengerTransportPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('mautic.messenger.transport_type')) {
            return;
        }

        $definition = $container->getDefinition('mautic.messenger.transport_type');

        $taggedServices = $container->findTaggedServiceIds('mautic.messenger_transport');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addTransport', [
                $id,
                !empty($tags[0][MessengerTransportType::TRANSPORT_ALIAS]) ? $tags[0][MessengerTransportType::TRANSPORT_ALIAS] : $id,
                !empty($tags[0][MessengerTransportType::FIELD_HOST]) ? $tags[0][MessengerTransportType::FIELD_HOST] : false,
                !empty($tags[0][MessengerTransportType::FIELD_PORT]) ? $tags[0][MessengerTransportType::FIELD_PORT] : false,
                !empty($tags[0][MessengerTransportType::FIELD_USER]) ? $tags[0][MessengerTransportType::FIELD_USER] : false,
                !empty($tags[0][MessengerTransportType::FIELD_PASSWORD]) ? $tags[0][MessengerTransportType::FIELD_PASSWORD] : false,
                !empty($tags[0][MessengerTransportType::TRANSPORT_OPTIONS]) ? $tags[0][MessengerTransportType::TRANSPORT_OPTIONS] : false,
                !empty($tags[0][MessengerTransportType::DSN_CONVERTOR]) ? $tags[0][MessengerTransportType::DSN_CONVERTOR] : false,
            ]);
        }
    }
}
