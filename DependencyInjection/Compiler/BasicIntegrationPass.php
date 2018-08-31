<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class SmsTransportPass.
 */
class BasicIntegrationPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds('mautic.basic_integration');

        foreach ($taggedServices as $id => $tags) {
            $definition = $container->findDefinition($id);
            $definition->addMethodCall('setRouter', [new Reference('router')]);
            $definition->addMethodCall('setEntityManager', [new Reference('doctrine.orm.entity_manager')]);
        }
    }
}
