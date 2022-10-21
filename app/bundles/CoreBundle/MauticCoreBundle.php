<?php

namespace Mautic\CoreBundle;

use Mautic\CoreBundle\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MauticCoreBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new Compiler\ConfiguratorPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new Compiler\DbalPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new Compiler\TemplatingPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new Compiler\TranslationsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -100);
        $container->addCompilerPass(new Compiler\TranslationLoaderPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
        $container->addCompilerPass(new Compiler\ModelPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new Compiler\UpdateStepPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new Compiler\PermissionsPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new Compiler\PreUpdateCheckPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
    }
}
