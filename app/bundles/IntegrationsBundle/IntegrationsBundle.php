<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle;

use Mautic\IntegrationsBundle\Bundle\AbstractPluginBundle;
use Mautic\IntegrationsBundle\DependencyInjection\Compiler\AuthenticationIntegrationPass;
use Mautic\IntegrationsBundle\DependencyInjection\Compiler\BuilderIntegrationPass;
use Mautic\IntegrationsBundle\DependencyInjection\Compiler\ConfigIntegrationPass;
use Mautic\IntegrationsBundle\DependencyInjection\Compiler\IntegrationsPass;
use Mautic\IntegrationsBundle\DependencyInjection\Compiler\SyncIntegrationsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class IntegrationsBundle extends AbstractPluginBundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new IntegrationsPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new AuthenticationIntegrationPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new SyncIntegrationsPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new ConfigIntegrationPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new BuilderIntegrationPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
    }
}
