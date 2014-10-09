<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle;

use Mautic\ApiBundle\DependencyInjection\Compiler\OAuthPass;
use Mautic\ApiBundle\DependencyInjection\Factory\ApiFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class MauticApiBundle
 *
 * @package Mautic\ApiBundle
 */
class MauticApiBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new OAuthPass());

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new ApiFactory());
    }

    public function getParent() {
        return 'FOSOAuthServerBundle';
    }
}
