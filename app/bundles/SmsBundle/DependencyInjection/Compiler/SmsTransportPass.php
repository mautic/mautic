<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\DependencyInjection\Compiler;

use Mautic\SmsBundle\Sms\TransportChain;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class SmsTransportPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition     = $container->getDefinition(TransportChain::class);
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
