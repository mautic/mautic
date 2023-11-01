<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class PluginConfiguration implements ConfigurationInterface
{
    // This is setup to validate plugin's composer.json
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('');

        $rootNode
            ->children()
                ->scalarNode('name')->cannotBeEmpty()->end()
                ->scalarNode('description')->cannotBeEmpty()->end()
                ->scalarNode('author')->cannotBeEmpty()->end()
                ->scalarNode('version')
                    ->validate()
                        ->ifTrue(function ($s) {
                            return 1 !== preg_match('#[0-9]+\.[0-9]+(\.[0-9]+)?#', $s);
                        })
                        ->thenInvalid('Invalid version definition')->end()
                    ->end()
                ->scalarNode('requires')->end()
                ->enumNode('type')->values(['mautic-plugin'])->end()
                ->scalarNode('license')->cannotBeEmpty()->end()
                ->scalarNode('icon')->cannotBeEmpty()->end()
                ->scalarNode('minimum_stability')->cannotBeEmpty()->end()
                ->arrayNode('authors')
                    ->requiresAtLeastOneElement()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('name')->end()
                            ->scalarNode('email')->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('require')
                        ->requiresAtLeastOneElement()
                        ->useAttributeAsKey('name')
                        ->prototype('scalar')->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
