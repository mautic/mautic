<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle;

use Mautic\IntegrationsBundle\Bundle\AbstractPluginBundle;
use Symfony\Component\Routing\Loader\PhpFileLoader;

class GrapesJsBuilderBundle extends AbstractPluginBundle
{
    /**
     * @param mixed[] $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Config'));
        $loader->load('services.php');
    }
}
