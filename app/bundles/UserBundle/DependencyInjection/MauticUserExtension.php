<?php

declare(strict_types=1);

namespace Mautic\UserBundle\DependencyInjection;

use Mautic\UserBundle\EventListener\SAMLSubscriber;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class MauticUserExtension extends Extension
{
    /**
     * @param mixed[] $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Config'));
        $loader->load('services.php');

        $samlEnabled = $container->getParameter('mautic.saml_enabled');
        if (true !== $samlEnabled) {
            $container->removeDefinition(SAMLSubscriber::class);
        }
    }
}
