<?php

namespace Mautic\CoreBundle;

use Mautic\CoreBundle\DependencyInjection\Compiler\ConfiguratorPass;
use Mautic\CoreBundle\DependencyInjection\Compiler\DbalPass;
use Mautic\CoreBundle\DependencyInjection\Compiler\ModelPass;
use Mautic\CoreBundle\DependencyInjection\Compiler\ORMPurgerPass;
use Mautic\CoreBundle\DependencyInjection\Compiler\PermissionsPass;
use Mautic\CoreBundle\DependencyInjection\Compiler\PreUpdateCheckPass;
use Mautic\CoreBundle\DependencyInjection\Compiler\ServicePass;
use Mautic\CoreBundle\DependencyInjection\Compiler\ShortenerServicePass;
use Mautic\CoreBundle\DependencyInjection\Compiler\TestPass;
use Mautic\CoreBundle\DependencyInjection\Compiler\TranslationLoaderPass;
use Mautic\CoreBundle\DependencyInjection\Compiler\TranslationsPass;
use Mautic\CoreBundle\DependencyInjection\Compiler\TwigPass;
use Mautic\CoreBundle\DependencyInjection\Compiler\UpdateStepPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MauticCoreBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ConfiguratorPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new DbalPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new TwigPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new TranslationsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -100);
        $container->addCompilerPass(new TranslationLoaderPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
        $container->addCompilerPass(new ModelPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new UpdateStepPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new PermissionsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new PreUpdateCheckPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new ServicePass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 1000);
        $container->addCompilerPass(new ShortenerServicePass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new ORMPurgerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -10);

        if ('test' === $container->getParameter('kernel.environment')) {
            $container->addCompilerPass(new TestPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        }
    }
}
