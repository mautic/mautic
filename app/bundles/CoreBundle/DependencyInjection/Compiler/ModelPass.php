<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class ModelPass.
 */
class ModelPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('mautic.model') as $id => $tags) {
            $definition = $container->findDefinition($id);
            $definition->addMethodCall('setEntityManager', [new Reference('doctrine.orm.entity_manager')]);
            $definition->addMethodCall('setSecurity', [new Reference('mautic.security')]);
            $definition->addMethodCall('setDispatcher', [new Reference('event_dispatcher')]);
            $definition->addMethodCall('setTranslator', [new Reference('translator')]);
            $definition->addMethodCall('setUserHelper', [new Reference('mautic.helper.user')]);
            $definition->addMethodCall('setCoreParametersHelper', [new Reference('mautic.helper.core_parameters')]);

            $modelClass = $definition->getClass();
            $reflected  = new \ReflectionClass($modelClass);

            if ($reflected->hasMethod('setRouter')) {
                $definition->addMethodCall('setRouter', [new Reference('router')]);
            }

            if ($reflected->hasMethod('setLogger')) {
                $definition->addMethodCall('setLogger', [new Reference('monolog.logger.mautic')]);
            }

            if ($reflected->hasMethod('setSession')) {
                $definition->addMethodCall('setSession', [new Reference('session')]);
            }

            // Temporary, for development purposes
            if ($reflected->hasProperty('factory')) {
                $definition->addMethodCall('setFactory', [new Reference('mautic.factory')]);
            }
        }
    }
}
