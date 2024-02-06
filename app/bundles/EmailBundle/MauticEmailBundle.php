<?php

declare(strict_types=1);

namespace Mautic\EmailBundle;

use Mautic\EmailBundle\DependencyInjection\Compiler\StatHelperPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MauticEmailBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new StatHelperPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
    }
}
