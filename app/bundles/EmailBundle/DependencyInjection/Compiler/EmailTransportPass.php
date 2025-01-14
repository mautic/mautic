<?php

namespace Mautic\EmailBundle\DependencyInjection\Compiler;

use Mautic\EmailBundle\Model\TransportType;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
        $taggedServices = $container->findTaggedServiceIds('mautic.email_transport');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addTransport', [
                $id,
                !empty($tags[0][TransportType::TRANSPORT_ALIAS]) ? $tags[0][TransportType::TRANSPORT_ALIAS] : $id,
                !empty($tags[0][TransportType::FIELD_HOST]),
                !empty($tags[0][TransportType::FIELD_PORT]),
                !empty($tags[0][TransportType::FIELD_USER]),
                !empty($tags[0][TransportType::FIELD_PASSWORD]),
                !empty($tags[0][TransportType::FIELD_API_KEY]),
            ]);
        }
    }
}
