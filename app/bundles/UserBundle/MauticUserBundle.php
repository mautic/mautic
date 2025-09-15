<?php

namespace Mautic\UserBundle;

use Mautic\UserBundle\DependencyInjection\Compiler\FormLoginAuthenticatorOptionsPass;
use Mautic\UserBundle\DependencyInjection\Compiler\OAuthReplacePass;
use Mautic\UserBundle\DependencyInjection\Compiler\SsoAuthenticatorPass;
use Mautic\UserBundle\DependencyInjection\Firewall\Factory\MauticSsoFactory;
use Mautic\UserBundle\DependencyInjection\Firewall\Factory\PluginFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
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
        $extension->addAuthenticatorFactory(new MauticSsoFactory());

        $container->addCompilerPass(new OAuthReplacePass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new SsoAuthenticatorPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new FormLoginAuthenticatorOptionsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
    }
}
