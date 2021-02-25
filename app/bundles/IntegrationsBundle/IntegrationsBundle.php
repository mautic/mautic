<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
        $container->addCompilerPass(new IntegrationsPass());
        $container->addCompilerPass(new AuthenticationIntegrationPass());
        $container->addCompilerPass(new SyncIntegrationsPass());
        $container->addCompilerPass(new ConfigIntegrationPass());
        $container->addCompilerPass(new BuilderIntegrationPass());
    }
}
