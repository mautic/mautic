<?php

namespace Mautic\EmailBundle;

use Mautic\EmailBundle\DependencyInjection\Compiler\EmailTransportPass;
use Mautic\EmailBundle\DependencyInjection\Compiler\SpoolTransportPass;
use Mautic\EmailBundle\DependencyInjection\Compiler\StatHelperPass;
use Mautic\EmailBundle\DependencyInjection\Compiler\SwiftmailerDynamicMailerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class MauticEmailBundle.
 */
class MauticEmailBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new SpoolTransportPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new EmailTransportPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new SwiftmailerDynamicMailerPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
        $container->addCompilerPass(new StatHelperPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
    }
}
