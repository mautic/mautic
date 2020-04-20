<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://www.mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class UpdateStepPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('mautic.update.step_provider')) {
            return;
        }

        $definition     = $container->getDefinition('mautic.update.step_provider');
        $taggedServices = $container->findTaggedServiceIds('mautic.update_step');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addStep', [new Reference($id)]);
        }
    }
}
