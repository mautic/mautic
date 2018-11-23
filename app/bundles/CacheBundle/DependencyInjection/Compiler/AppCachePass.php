<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CacheBundle\DependencyInjection\Compiler;

use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class IntegrationPass.
 */
class AppCachePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $selectedAdapter = $container->getParameter('mautic.cache_adapter');

        foreach ($container->findTaggedServiceIds('mautic.cache.adapter') as $id => $tags) {
            $definition             = $container->findDefinition($id);
            $availableAdapters[$id] = $definition;
        }

        if (!isset($availableAdapters[$selectedAdapter])) {
            throw new InvalidArgumentException('Requested cache adapter "'.$selectedAdapter.'" is not available');
        }

        $cacheServiceDefinition = $container->findDefinition('mautic.cache.provider');
        $cacheServiceDefinition->addArgument($selectedAdapter);
        $cacheServiceDefinition->addMethodCall('setCacheAdapter', [new Reference($selectedAdapter)]);
    }
}
