<?php

namespace Mautic\EmailBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class StatHelperPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // replace manual tagging with autoconfiguration
        $container->registerForAutoconfiguration(\Mautic\EmailBundle\Stats\Helper\StatHelperInterface::class)
            ->addTag('mautic.email_stat_helper');
    }
}
