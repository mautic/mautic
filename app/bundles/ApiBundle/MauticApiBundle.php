<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle;

use Mautic\ApiBundle\DependencyInjection\Compiler\OAuthPass;
use Mautic\ApiBundle\DependencyInjection\Compiler\SerializerPass;
use Mautic\ApiBundle\DependencyInjection\Factory\ApiFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class MauticApiBundle.
 */
class MauticApiBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new OAuthPass());
        $container->addCompilerPass(new SerializerPass());

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new ApiFactory());
    }
}
