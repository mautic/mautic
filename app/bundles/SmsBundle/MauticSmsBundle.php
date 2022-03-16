<?php

namespace Mautic\SmsBundle;

use Mautic\PluginBundle\Bundle\PluginBundleBase;
use Mautic\SmsBundle\DependencyInjection\Compiler\SmsTransportPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class MauticSmsBundle.
 */
class MauticSmsBundle extends PluginBundleBase
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new SmsTransportPass());
    }
}
