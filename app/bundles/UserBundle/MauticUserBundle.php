<?php

namespace Mautic\UserBundle;

use Mautic\UserBundle\DependencyInjection\Firewall\Factory\PluginFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MauticUserBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new PluginFactory());
    }
}
