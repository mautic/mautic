<?php

namespace Mautic\ApiBundle\DependencyInjection\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ApiFactory implements SecurityFactoryInterface
{
    public function create(ContainerBuilder $container, string $id, array $config, string $userProvider, ?string $defaultEntryPoint): array
    {
        $providerId = 'security.authentication.provider.mautic_api.'.$id;
        $container
            ->setDefinition($providerId, new ChildDefinition('mautic_api.security.authentication.provider'))
            ->replaceArgument(0, new Reference($userProvider));

        $listenerId = 'security.authentication.listener.mautic_api.'.$id;
        $container->setDefinition($listenerId, new ChildDefinition('mautic_api.security.authentication.listener'));

        return [$providerId, $listenerId, $defaultEntryPoint];
    }

    public function getPosition()
    {
        return 'pre_auth';
    }

    public function getKey()
    {
        return 'mautic_api_auth';
    }

    public function addConfiguration(NodeDefinition $node): void
    {
    }
}
