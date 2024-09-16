<?php

namespace Mautic\ApiBundle;

use Mautic\ApiBundle\DependencyInjection\Compiler\SerializerPass;
use Mautic\ApiBundle\DependencyInjection\Factory\ApiFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MauticApiBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new SerializerPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);

        $extension = $container->getExtension('security');
        \assert($extension instanceof SecurityExtension);
        $extension->addAuthenticatorFactory(new ApiFactory());
    }
}
