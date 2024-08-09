<?php

namespace Mautic\UserBundle\DependencyInjection\Firewall\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PluginFactory implements SecurityFactoryInterface
{
    public function create(ContainerBuilder $container, string $id, array $config, string $userProvider, ?string $defaultEntryPoint): array
    {
        $providerId = 'security.authentication.provider.mautic.'.$id;
        $container->setDefinition($providerId, new ChildDefinition('mautic.user.preauth_authenticator'))
            ->replaceArgument(3, new Reference($userProvider))
            ->replaceArgument(4, $id);

        $listenerId = 'security.authentication.listener.mautic.'.$id;
        $container->setDefinition($listenerId, new ChildDefinition('mautic.security.authentication_listener'))
            ->replaceArgument(5, $id);

        return [$providerId, $listenerId, $defaultEntryPoint];
    }

    /**
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
