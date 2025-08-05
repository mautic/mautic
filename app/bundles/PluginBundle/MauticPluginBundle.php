<?php

declare(strict_types=1);

namespace Mautic\PluginBundle;

use Mautic\PluginBundle\Tests\DependencyInjection\Compiler\TestPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MauticPluginBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        if ('test' === $container->getParameter('kernel.environment')) {
            $container->addCompilerPass(new TestPass());
        }
    }
}
