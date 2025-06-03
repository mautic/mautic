<?php

declare(strict_types=1);

namespace Mautic\CacheBundle;

use Mautic\CacheBundle\DependencyInjection\Compiler\CsrfPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MauticCacheBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CsrfPass());
    }
}
