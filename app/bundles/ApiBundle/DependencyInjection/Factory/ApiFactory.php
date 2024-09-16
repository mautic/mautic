<?php

namespace Mautic\ApiBundle\DependencyInjection\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ApiFactory implements AuthenticatorFactoryInterface
{
    public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): string|array
    {
        $providerId = 'security.authentication.provider.mautic_api.'.$firewallName;
        $container
            ->setDefinition($providerId, new ChildDefinition('mautic_api.security.authentication.provider'))
            ->replaceArgument(0, new Reference($userProviderId));

        $listenerId = 'security.authentication.listener.mautic_api.'.$firewallName;
        $container->setDefinition($listenerId, new ChildDefinition('mautic_api.security.authentication.listener'));

        return [$providerId, $listenerId];
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function getKey(): string
    {
        return 'mautic_api_auth';
    }

    public function addConfiguration(NodeDefinition $node): void
    {
    }
}
