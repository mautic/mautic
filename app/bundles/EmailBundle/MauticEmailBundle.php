<?php

namespace Mautic\EmailBundle;

use Mautic\EmailBundle\DependencyInjection\Compiler\EmailTransportPass;
use Mautic\EmailBundle\DependencyInjection\Compiler\StatHelperPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MauticEmailBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new EmailTransportPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new StatHelperPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
    }
}
