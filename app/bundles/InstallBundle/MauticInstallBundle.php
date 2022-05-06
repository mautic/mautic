<?php

declare(strict_types=1);

namespace Mautic\InstallBundle;

use Mautic\InstallBundle\DependencyInjection\Compiler\InstallCommandPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class MauticInstallBundle.
 */
class MauticInstallBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new InstallCommandPass());
    }
}
