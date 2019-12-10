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

namespace MauticPlugin\IntegrationsBundle;

use MauticPlugin\IntegrationsBundle\Bundle\AbstractPluginBundle;
use MauticPlugin\IntegrationsBundle\DependencyInjection\Compiler\AuthenticationIntegrationPass;
use MauticPlugin\IntegrationsBundle\DependencyInjection\Compiler\ConfigIntegrationPass;
use MauticPlugin\IntegrationsBundle\DependencyInjection\Compiler\IntegrationsPass;
use MauticPlugin\IntegrationsBundle\DependencyInjection\Compiler\SyncIntegrationsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class IntegrationsBundle extends AbstractPluginBundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new IntegrationsPass());
        $container->addCompilerPass(new AuthenticationIntegrationPass());
        $container->addCompilerPass(new SyncIntegrationsPass());
        $container->addCompilerPass(new ConfigIntegrationPass());
    }
}
