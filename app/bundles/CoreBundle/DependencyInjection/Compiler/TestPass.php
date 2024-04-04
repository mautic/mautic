<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class TestPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Stub Guzzle HTTP client to prevent accidental request to third parties
        $definition = $container->getDefinition('mautic.http.client');
        $definition->setPublic(true)
            ->setFactory([\Mautic\CoreBundle\Test\Guzzle\ClientFactory::class, 'stub'])
            ->addArgument(new Reference(\GuzzleHttp\Handler\MockHandler::class));

        $container->removeAlias(\Symfony\Contracts\HttpClient\HttpClientInterface::class);
        $container->register(\Symfony\Component\HttpClient\MockHttpClient::class, \Symfony\Component\HttpClient\MockHttpClient::class)->setAutowired(true)->setPublic(true);
        $container->setAlias(\Symfony\Contracts\HttpClient\HttpClientInterface::class, \Symfony\Component\HttpClient\MockHttpClient::class);
    }
}
