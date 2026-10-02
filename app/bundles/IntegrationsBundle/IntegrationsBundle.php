<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle;

use Mautic\IntegrationsBundle\Bundle\AbstractPluginBundle;
use Mautic\IntegrationsBundle\DependencyInjection\Compiler\TestPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class IntegrationsBundle extends AbstractPluginBundle
{
    public function build(ContainerBuilder $container): void
    {
        if ('test' === $container->getParameter('kernel.environment')) {
            $container->addCompilerPass(new TestPass());
        }
    }
}
