<?php

namespace Mautic\CoreBundle;

use Mautic\CoreBundle\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MauticCoreBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new Compiler\RequirementsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new Compiler\ConfiguratorPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new Compiler\DbalPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new Compiler\TwigPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new Compiler\TranslationsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -100);
        $container->addCompilerPass(new Compiler\TranslationLoaderPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
        $container->addCompilerPass(new Compiler\ModelPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new Compiler\UpdateStepPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new Compiler\PermissionsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new Compiler\PreUpdateCheckPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new Compiler\ServicePass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1000);
        $container->addCompilerPass(new Compiler\ShortenerServicePass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new Compiler\ORMPurgerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -10);
        $container->addCompilerPass(new Compiler\SystemThemeTemplatePathPass(), PassConfig::TYPE_BEFORE_REMOVING, 0);

        if ('test' === $container->getParameter('kernel.environment')) {
            $container->addCompilerPass(new Compiler\TestPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        }
    }
}
