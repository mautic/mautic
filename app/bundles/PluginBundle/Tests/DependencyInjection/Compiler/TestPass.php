<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Tests\DependencyInjection\Compiler;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Mautic\PluginBundle\Tests\Integration\ClientFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TestPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->register(ClientFactory::class, ClientFactory::class)
            ->setArguments([new Reference('mautic.http.client')]);

        foreach ($container->getDefinitions() as $definition) {
            $class = (string) $definition->getClass();

            /** @phpstan-ignore-next-line  Ignore as AbstractIntegration is deprecated */
            if (str_starts_with($class, 'Mautic') && is_subclass_of($class, AbstractIntegration::class)) {
                $definition->addMethodCall('setClientFactory', [new Reference(ClientFactory::class)]);
            }
        }
    }
}
