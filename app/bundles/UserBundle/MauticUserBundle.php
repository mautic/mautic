<?php

namespace Mautic\UserBundle;

use Mautic\UserBundle\DependencyInjection\Firewall\Factory\PluginFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MauticUserBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        \assert($extension instanceof SecurityExtension);
        $extension->addAuthenticatorFactory(new PluginFactory());
    }
}
