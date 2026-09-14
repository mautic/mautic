<?php

/*
 * This file is part of the LightSAML SP-Bundle package.
 *
 * (c) Milos Tomic <tmilos@lightsaml.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LightSaml\SpBundle\DependencyInjection\Security\Factory;

use Exception;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AbstractFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\AuthenticatorFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LightSamlSpFactory extends AbstractFactory implements AuthenticatorFactoryInterface
{
    public function addConfiguration(NodeDefinition $node): void
    {
        parent::addConfiguration($node);

        $node
            ->children()
                ->scalarNode('username_mapper')->defaultValue('lightsaml_sp.username_mapper.simple')->end()
                ->scalarNode('user_creator')->defaultNull()->end()
                ->scalarNode('attribute_mapper')->defaultValue('lightsaml_sp.attribute_mapper.simple')->end()
                ->scalarNode('token_factory')->defaultValue('lightsaml_sp.token_factory')->end()
            ->end()
        ->end();
    }

    public function getPosition(): string
    {
        return 'form';
    }

    public function getPriority(): int
    {
        return -30;
    }

    public function getKey(): string
    {
        return 'light_saml_sp';
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $firewallName
     * @param mixed[]          $config
     * @param string           $userProviderId
     *
     * @return string
     */
    public function createAuthenticator(
        ContainerBuilder $container,
        string $firewallName,
        array $config,
        string $userProviderId,
    ): string
    {
        $authenticatorId = 'security.authenticator.lightsaml_sp.' . $firewallName;

        $container
            ->setDefinition($authenticatorId, new ChildDefinition('security.authenticator.lightsaml_sp'))
            ->replaceArgument(0, $firewallName)
            ->replaceArgument(3, isset($config['provider']) ? new Reference($userProviderId) : null)
            ->replaceArgument(4, isset($config['username_mapper']) ? new Reference($config['username_mapper']) : null)
            ->replaceArgument(5, isset($config['user_creator']) ? new Reference($config['user_creator']) : null)
            ->replaceArgument(6, isset($config['attribute_mapper']) ? new Reference($config['attribute_mapper']) : null)
            ->replaceArgument(7, isset($config['token_factory']) ? new Reference($config['token_factory']) : null)
            ->replaceArgument(8, new Reference($this->createAuthenticationSuccessHandler($container, $firewallName, $config)))
            ->replaceArgument(9, new Reference($this->createAuthenticationFailureHandler($container, $firewallName, $config)))
            ->replaceArgument(10, $config);

        return $authenticatorId;
    }

    protected function createAuthProvider(ContainerBuilder $container, $id, $config, $userProviderId)
    {
        throw new Exception('The old authentication system is not supported with light_saml_sp.');
    }

    protected function getListenerId()
    {
        throw new Exception('The old authentication system is not supported with light_saml_sp.');
    }

    protected function createEntryPoint($container, $id, $config, $defaultEntryPointId)
    {
        throw new Exception('The old authentication system is not supported with light_saml_sp.');
    }
}
