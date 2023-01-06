<?php

namespace Mautic\CampaignBundle;

use Mautic\CampaignBundle\DependencyInjection\CompilerPass\TestExecutionerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MauticCampaignBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new TestExecutionerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 0);
    }
}
