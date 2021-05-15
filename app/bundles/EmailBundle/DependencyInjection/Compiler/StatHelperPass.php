<?php
/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class StatHelperPass.
 */
class StatHelperPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition     = $container->getDefinition('mautic.email.stats.helper_container');
        $taggedServices = $container->findTaggedServiceIds('mautic.email_stat_helper');
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addHelper', [
                new Reference($id),
            ]);
        }
    }
}
