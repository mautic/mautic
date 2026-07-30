<?php

namespace Mautic\CoreBundle\DependencyInjection;

use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class MauticCoreExtension extends Extension
{
    public const DEFAULT_EXCLUDES = [
        'Config',
        'Crate',
        'DataObject',
        'DependencyInjection',
        'DTO',
        'Entity',
        'Event',
        'Exception',
        'Migration',
        'Migrations',
        'Security',
        'Test',
        'Tests',
        'Views',
    ];

    /**
     * @param mixed[] $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // A permission object is picked up by PermissionsPass through this tag, no need to spell it out
        $container->registerForAutoconfiguration(AbstractPermissions::class)
            ->addTag('mautic.permissions');

        // For the project:
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../config'));
        $loader->load('services.php');

        // For the CoreBundle
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Config'));
        $loader->load('services.php');
    }
}
