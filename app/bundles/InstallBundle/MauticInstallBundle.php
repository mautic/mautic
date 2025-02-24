<?php

declare(strict_types=1);

namespace Mautic\InstallBundle;

use Mautic\InstallBundle\DependencyInjection\Compiler\InstallCommandPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MauticInstallBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new InstallCommandPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
    }
}
