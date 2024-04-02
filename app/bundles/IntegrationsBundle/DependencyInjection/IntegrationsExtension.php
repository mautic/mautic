<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\DependencyInjection;

use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\BuilderInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\IntegrationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class IntegrationsExtension extends Extension
{
    /**
     * @param mixed[] $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Config'));
        $loader->load('services.php');

        $container->registerForAutoconfiguration(IntegrationInterface::class)
            ->addTag('mautic.integration');
        $container->registerForAutoconfiguration(BasicInterface::class)
            ->addTag('mautic.basic_integration');
        $container->registerForAutoconfiguration(ConfigFormInterface::class)
            ->addTag('mautic.config_integration');
        $container->registerForAutoconfiguration(BuilderInterface::class)
            ->addTag('mautic.builder_integration');
    }
}
