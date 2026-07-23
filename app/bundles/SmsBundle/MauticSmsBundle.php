<?php

namespace Mautic\SmsBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;
use Mautic\SmsBundle\DependencyInjection\Compiler\SmsTransportPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MauticSmsBundle extends PluginBundleBase
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new SmsTransportPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
    }
}
