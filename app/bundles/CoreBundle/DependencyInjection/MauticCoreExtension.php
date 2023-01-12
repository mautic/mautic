<?php

namespace Mautic\CoreBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class MauticCoreExtension extends Extension
{
    public const DEFAULT_EXCLUDES = [
        'DependencyInjection',
        'Entity',
        'Config',
        'Test',
        'Tests',
        'Views',
        'Event',
        'Exception',
        'Crate',
        'DataObject',
        'DTO',
        'Migrations',
        'Migration',
        'Form/DataTransformer',
        'Security',
        'Controller', // Enabling this will require to refactor all controllers to use DI.
    ];

    /**
     * @param mixed[] $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // For the project:
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../config'));
        $loader->load('services.php');

        // For the CoreBundle
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Config'));
        $loader->load('services.php');
    }
}
