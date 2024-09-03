<?php

namespace Mautic\UserBundle\DependencyInjection\Firewall\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PluginFactory implements AuthenticatorFactoryInterface
{
    public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): string|array
    {
        $providerId = 'security.authentication.provider.mautic.'.$firewallName;
        $container->setDefinition($providerId, new ChildDefinition('mautic.user.preauth_authenticator'))
            ->replaceArgument(3, new Reference($userProviderId))
            ->replaceArgument(4, $firewallName);

        $listenerId = 'security.authentication.listener.mautic.'.$firewallName;
        $container->setDefinition($listenerId, new ChildDefinition('mautic.security.authentication_listener'))
            ->replaceArgument(5, $firewallName);

        return [$providerId, $listenerId];
    }

    public function getPriority(): int
    {
        return 0;
    }

    public function getKey(): string
    {
        return 'mautic_plugin_auth';
    }

    public function addConfiguration(NodeDefinition $node): void
    {
    }
}
