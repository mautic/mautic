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

        $container->addCompilerPass(new SpoolTransportPass());
        $container->addCompilerPass(new EmailTransportPass());
        $container->addCompilerPass(new SwiftmailerDynamicMailerPass());
        $container->addCompilerPass(new StatHelperPass());
    }
}
