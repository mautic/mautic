<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class SmsTransportPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has('mautic.sms.transport_chain')) {
            return;
        }

        $definition     = $container->getDefinition('mautic.sms.transport_chain');
        $taggedServices = $container->findTaggedServiceIds('mautic.sms_transport');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addTransport', [
                $id,
                new Reference($id),
                !empty($tags[0]['alias']) ? $tags[0]['alias'] : $id,
                !empty($tags[0]['integrationAlias']) ? $tags[0]['integrationAlias'] : $id,
            ]);
        }
    }
}
