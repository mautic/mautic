<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\DependencyInjection\Compiler;

use MauticPlugin\MauticCrmBundle\Tests\Pipedrive\Mock\Client;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class TestPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->register('mautic_integration.pipedrive.guzzle.client', Client::class)->setPublic(true);
    }
}
