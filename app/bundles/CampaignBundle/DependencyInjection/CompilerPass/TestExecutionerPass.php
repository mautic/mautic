<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\DependencyInjection\CompilerPass;

use Mautic\CampaignBundle\Executioner\TestInactiveExecutioner;
use Mautic\CampaignBundle\Executioner\TestScheduledExecutioner;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class TestExecutionerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ('test' !== $container->getParameter('kernel.environment')) {
            return;
        }

        $inactiveExecutionerArguments = $container->getDefinition('mautic.campaign.executioner.inactive')->getArguments();
        $container->register(TestInactiveExecutioner::class, TestInactiveExecutioner::class)
            ->setDecoratedService('mautic.campaign.executioner.inactive', 'mautic.campaign.executioner.inactive.inner', -100)
            ->setArguments($inactiveExecutionerArguments)
            ->addTag('kernel.reset', ['method' => 'reset']);

        $scheduledExecutionerArguments = $container->getDefinition('mautic.campaign.executioner.scheduled')->getArguments();
        $container->register(TestScheduledExecutioner::class, TestScheduledExecutioner::class)
            ->setDecoratedService('mautic.campaign.executioner.scheduled', 'mautic.campaign.executioner.scheduled.inner', -100)
            ->setArguments($scheduledExecutionerArguments);
    }
}
