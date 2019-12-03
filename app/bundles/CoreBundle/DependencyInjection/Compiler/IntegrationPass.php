<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
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
 * Class IntegrationPass.
 */
class IntegrationPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('mautic.integration') as $id => $tags) {
            $definition = $container->findDefinition($id);

            /*
             * @deprecated: To be removed in 3.0. Set dependencies on Integration constructor instead,
             *              using the service container config.php to pass those dependencies in.
             */

            $class     = $definition->getClass();
            $reflected = new \ReflectionClass($class);

            if ($reflected->hasMethod('setIntegrationHelper')) {
                $definition->addMethodCall('setIntegrationHelper', [new Reference('mautic.helper.integration')]);
            }
        }
    }
}
