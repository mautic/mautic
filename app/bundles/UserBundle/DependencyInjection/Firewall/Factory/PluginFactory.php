<?php

namespace Mautic\UserBundle\DependencyInjection\Firewall\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PluginFactory implements AuthenticatorFactoryInterface
{
    public const PRIORITY = -30;

    public function createAuthenticator(ContainerBuilder $container, string $firewallName, array $config, string $userProviderId): string
    {
        $authenticatorId = 'security.authentication.provider.mautic.'.$firewallName;

        $authenticator = $container
            ->setDefinition($authenticatorId, new ChildDefinition('security.authenticator.mautic_api'))
            ->replaceArgument('$firewallName', $firewallName)
            ->replaceArgument('$userProvider', new Reference($userProviderId));

        $container->setDefinition($authenticatorId, $authenticator);

        return $authenticatorId;
    }

    public function getPriority(): int
    {
        return self::PRIORITY;
    }

    public function getKey(): string
    {
        return 'mautic_plugin_auth';
    }

    public function addConfiguration(NodeDefinition $node): void
    {
    }
}
