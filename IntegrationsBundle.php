<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;
use MauticPlugin\IntegrationsBundle\DependencyInjection\Compiler\AuthenticationIntegrationPass;
use MauticPlugin\IntegrationsBundle\DependencyInjection\Compiler\DispatcherIntegrationPass;
use MauticPlugin\IntegrationsBundle\DependencyInjection\Compiler\EncryptionIntegrationPass;
use MauticPlugin\IntegrationsBundle\DependencyInjection\Compiler\SyncIntegrationsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class IntegrationsBundle
 *
 * @package MauticPlugin\IntegrationsBundle
 */
class IntegrationsBundle extends PluginBundleBase
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new EncryptionIntegrationPass());
        $container->addCompilerPass(new DispatcherIntegrationPass());
        $container->addCompilerPass(new AuthenticationIntegrationPass());
        $container->addCompilerPass(new SyncIntegrationsPass());
    }
}
