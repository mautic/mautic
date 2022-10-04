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
        $container->addCompilerPass(new Compiler\ConfiguratorPass());
        $container->addCompilerPass(new Compiler\DbalPass());
        $container->addCompilerPass(new Compiler\TemplatingPass());
        $container->addCompilerPass(new Compiler\TranslationsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -100);
        $container->addCompilerPass(new Compiler\TranslationLoaderPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
        $container->addCompilerPass(new Compiler\ModelPass());
        $container->addCompilerPass(new Compiler\UpdateStepPass());
        $container->addCompilerPass(new Compiler\PermissionsPass());
        $container->addCompilerPass(new Compiler\PreUpdateCheckPass());
    }
}
