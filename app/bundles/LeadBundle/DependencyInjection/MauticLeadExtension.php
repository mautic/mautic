<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\DependencyInjection;

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class MauticLeadExtension extends MauticCoreExtension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Config'));
        $loader->load('services.php');

        $this->configureBundles($container, [$container->getParameter('mautic.bundles')['MauticLeadBundle']]);
    }
}
