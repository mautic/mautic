<?php

namespace Mautic\CoreBundle;

use Mautic\CoreBundle\DependencyInjection\Compiler\ConfiguratorPass;
use Mautic\CoreBundle\DependencyInjection\Compiler\DbalPass;
use Mautic\CoreBundle\DependencyInjection\Compiler\DoctrineMappingDriverPass;
use Mautic\CoreBundle\DependencyInjection\Compiler\ORMPurgerPass;
use Mautic\CoreBundle\DependencyInjection\Compiler\RequirementsPass;
use Mautic\CoreBundle\DependencyInjection\Compiler\SystemThemeTemplatePathPass;
use Mautic\CoreBundle\DependencyInjection\Compiler\TestPass;
use Mautic\CoreBundle\DependencyInjection\Compiler\TranslationLoaderPass;
use Mautic\CoreBundle\DependencyInjection\Compiler\TranslationsPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class MauticCoreBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RequirementsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new ConfiguratorPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new DbalPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new DoctrineMappingDriverPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new TranslationsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -100);
        $container->addCompilerPass(new TranslationLoaderPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);

        $container->addCompilerPass(new ORMPurgerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -10);
        $container->addCompilerPass(new SystemThemeTemplatePathPass(), PassConfig::TYPE_BEFORE_REMOVING, 0);

        if ('test' === $container->getParameter('kernel.environment')) {
            $container->addCompilerPass(new TestPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        }
    }
}
