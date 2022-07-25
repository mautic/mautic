<?php

namespace Mautic\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SpoolTransportPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        // Override Swiftmailer's \Swift_TransportSpool with our own that delegates writing to the filesystem or sending immediately
        // based on the configuration
        $container->setAlias('swiftmailer.mailer.default.transport', 'mautic.transport.spool');
    }
}
