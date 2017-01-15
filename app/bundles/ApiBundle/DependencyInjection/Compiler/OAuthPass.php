<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class OAuthPass.
 */
class OAuthPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('bazinga.oauth.security.authentication.provider')) {
            //Add a addMethodCall to set factory
            $container->getDefinition('bazinga.oauth.security.authentication.provider')->addMethodCall(
                'setFactory', [new Reference('mautic.factory')]
            );
        }

        if ($container->hasDefinition('bazinga.oauth.security.authentication.listener')) {
            //Add a addMethodCall to set factory
            $container->getDefinition('bazinga.oauth.security.authentication.listener')->addMethodCall(
                'setFactory', [new Reference('mautic.factory')]
            );
        }

        if ($container->hasDefinition('fos_oauth_server.security.authentication.listener')) {
            //Add a addMethodCall to set factory
            $container->getDefinition('fos_oauth_server.security.authentication.listener')->addMethodCall(
                'setFactory', [new Reference('mautic.factory')]
            );
        }
    }
}
