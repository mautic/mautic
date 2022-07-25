<?php

namespace Mautic\ApiBundle;

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

        $container->addCompilerPass(new SerializerPass());

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new ApiFactory());
    }
}
