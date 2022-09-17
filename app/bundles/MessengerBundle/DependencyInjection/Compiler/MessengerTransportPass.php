<?php

namespace Mautic\MessengerBundle\DependencyInjection\Compiler;

use Mautic\MessengerBundle\Model\TransportType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class MessengerTransportPass.
 */
class MessengerTransportPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('mautic.messenger.transport_type')) {
            return;
        }

        $definition = $container->getDefinition('mautic.messenger.transport_type');

        $taggedServices = $container->findTaggedServiceIds('mautic.messenger_transport');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addTransport', [
                $id,
                !empty($tags[0][TransportType::TRANSPORT_ALIAS]) ? $tags[0][TransportType::TRANSPORT_ALIAS] : $id,
                !empty($tags[0][TransportType::TRANSPORT_OPTIONS]) ? $tags[0][TransportType::TRANSPORT_OPTIONS] : '',
            ]);
        }
    }
}
