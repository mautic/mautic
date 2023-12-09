<?php

namespace Mautic\ApiBundle;

use Mautic\ApiBundle\DependencyInjection\Compiler\SerializerPass;
use Mautic\ApiBundle\DependencyInjection\Factory\ApiFactory;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MauticApiBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new SerializerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new ApiFactory());
    }
}
