<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\DependencyInjection\Compiler;

use GuzzleHttp\Handler\MockHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TestPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('mautic.integrations.auth_provider.oauth2threelegged');
        $definition->setFactory('\Mautic\IntegrationsBundle\Tests\Functional\Auth\Provider\Oauth2ThreeLegged\HttpFactory::factory');
        $definition->addArgument(new Reference(MockHandler::class));
    }
}
