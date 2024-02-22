<?php

namespace Mautic\SmsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SmsTransportPass implements CompilerPassInterface
{
    private ?\Symfony\Component\DependencyInjection\ContainerBuilder $container = null;

    public function process(ContainerBuilder $container): void
    {
        $this->container = $container;

        $this->registerTransports();
        $this->registerCallbacks();
    }

    private function registerTransports(): void
    {
        if (!$this->container->has('mautic.sms.transport_chain')) {
            return;
        }

        $definition     = $this->container->getDefinition('mautic.sms.transport_chain');
        $taggedServices = $this->container->findTaggedServiceIds('mautic.sms_transport');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addTransport', [
                $id,
                new Reference($id),
                !empty($tags[0]['alias']) ? $tags[0]['alias'] : $id,
                !empty($tags[0]['integrationAlias']) ? $tags[0]['integrationAlias'] : $id,
            ]);
        }
    }

    private function registerCallbacks(): void
    {
        if (!$this->container->has('mautic.sms.callback_handler_container')) {
            return;
        }

        $definition     = $this->container->getDefinition('mautic.sms.callback_handler_container');
        $taggedServices = $this->container->findTaggedServiceIds('mautic.sms_callback_handler');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('registerHandler', [
                new Reference($id),
            ]);
        }
    }
}
