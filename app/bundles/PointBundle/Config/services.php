<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
    ];

    $services->load('Mautic\\PointBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\PointBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);

    $services->alias('mautic.point.model.point', Mautic\PointBundle\Model\PointModel::class);
    $services->alias('mautic.point.model.triggerevent', Mautic\PointBundle\Model\TriggerEventModel::class);
    $services->alias('mautic.point.model.trigger', Mautic\PointBundle\Model\TriggerModel::class);
    $services->alias('mautic.point.model.group', Mautic\PointBundle\Model\PointGroupModel::class);
    $services->alias('mautic.point.repository.lead_point_log', Mautic\PointBundle\Entity\LeadPointLogRepository::class);
    $services->alias('mautic.point.repository.lead_trigger_log', Mautic\PointBundle\Entity\LeadTriggerLogRepository::class);
};
