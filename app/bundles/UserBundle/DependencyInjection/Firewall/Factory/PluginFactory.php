<?php

namespace Mautic\UserBundle\DependencyInjection\Firewall\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PluginFactory implements AuthenticatorFactoryInterface, SecurityFactoryInterface
{
    public const PRIORITY = -30;

    /**
     * @deprecated Remove in Mautic 6.0. Use new authentication system.
     *
     * @param array<mixed> $config
     *
     * @return array<mixed>
     */
    public function create(ContainerBuilder $container, string $id, array $config, string $userProviderId, ?string $defaultEntryPointId): array
    {
        $providerId = 'security.authentication.provider.mautic.'.$id;
        $container->setDefinition($providerId, new ChildDefinition('mautic.user.preauth_authenticator'))
            ->replaceArgument(3, new Reference($userProviderId))
            ->replaceArgument(4, $id);

        $listenerId = 'security.authentication.listener.mautic.'.$id;
        $container->setDefinition($listenerId, new ChildDefinition('mautic.security.authentication_listener'))
            ->replaceArgument(5, $id);

        return [$providerId, $listenerId, $defaultEntryPointId];
    }

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

    /**
     * @deprecated Remove in Mautic 6.0. Use new authentication system.
     *
     * @return string
     */
    public function getPosition()
    {
        return 'pre_auth';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'mautic_plugin_auth';
    }

    public function addConfiguration(NodeDefinition $node): void
    {
    }
}
