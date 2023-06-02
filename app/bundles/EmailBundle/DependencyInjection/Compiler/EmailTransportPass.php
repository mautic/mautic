<?php

namespace Mautic\EmailBundle\DependencyInjection\Compiler;

use Mautic\EmailBundle\Model\TransportType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class EmailTransportPass.
 */
class EmailTransportPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('mautic.email.transport_type')) {
            return;
        }

        $definition     = $container->getDefinition('mautic.email.transport_type');
        $taggedServices = $container->findTaggedServiceIds('mautic.email.transport_extension');
        $wrapper        = $container->getDefinition('mautic.email.transport_wrapper');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addTransport', [
                $id,
                !empty($tags[0][TransportType::TRANSPORT_ALIAS]) ? $tags[0][TransportType::TRANSPORT_ALIAS] : $id,
                !empty($tags[0][TransportType::FIELD_HOST]) ? $tags[0][TransportType::FIELD_HOST] : false,
                !empty($tags[0][TransportType::FIELD_PORT]) ? $tags[0][TransportType::FIELD_PORT] : false,
                !empty($tags[0][TransportType::FIELD_USER]) ? $tags[0][TransportType::FIELD_USER] : false,
                !empty($tags[0][TransportType::FIELD_PASSWORD]) ? $tags[0][TransportType::FIELD_PASSWORD] : false,
                !empty($tags[0][TransportType::FIELD_API_KEY]) ? $tags[0][TransportType::FIELD_API_KEY] : false,
                !empty($tags[0][TransportType::TRANSPORT_OPTIONS]) ? $tags[0][TransportType::TRANSPORT_OPTIONS] : false,
                !empty($tags[0][TransportType::DSN_CONVERTOR]) ? $tags[0][TransportType::DSN_CONVERTOR] : false,
            ]);
            $wrapper->addMethodCall('addTransportExtension', [new Reference($id)]);
        }
    }
}
