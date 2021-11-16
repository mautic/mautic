<?php

declare(strict_types=1);

namespace Mautic\CacheBundle;

use Mautic\CacheBundle\DependencyInjection\Compiler\CsrfPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MauticCacheBundle extends Bundle
{
    /**
     * @return void
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CsrfPass());
    }
}
