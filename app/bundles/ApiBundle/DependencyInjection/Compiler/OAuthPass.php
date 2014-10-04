<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


namespace Mautic\ApiBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OAuthPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('bazinga.oauth.security.authentication.provider')) {
            //Add a addMethodCall to set factory
            $container->getDefinition('bazinga.oauth.security.authentication.provider')->addMethodCall(
                'setFactory', array(new Reference('mautic.factory'))
            );
        }
    }
}