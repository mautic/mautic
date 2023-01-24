<?php

namespace Mautic\MessengerBundle;

use Mautic\MessengerBundle\DependencyInjection\Compiler\MessengerTransportPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class MessengerBundle.
 */
class MauticMessengerBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new MessengerTransportPass(), \Symfony\Component\DependencyInjection\Compiler\PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
    }
}
