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
        'ProgressiveProfiling/DisplayCounter.php',
        'ProgressiveProfiling/DisplayManager.php',
    ];

    $services->load('Mautic\\FormBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\FormBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);

    $services->alias('mautic.form.model.action', Mautic\FormBundle\Model\ActionModel::class);
    $services->alias('mautic.form.model.field', Mautic\FormBundle\Model\FieldModel::class);
    $services->alias('mautic.form.model.form', Mautic\FormBundle\Model\FormModel::class);
    $services->alias('mautic.form.model.submission', Mautic\FormBundle\Model\SubmissionModel::class);
    $services->alias('mautic.form.model.submission_result_loader', Mautic\FormBundle\Model\SubmissionResultLoader::class);
    $services->alias('mautic.form.repository.form', Mautic\FormBundle\Entity\FormRepository::class);
    $services->alias('mautic.form.repository.submission', Mautic\FormBundle\Entity\SubmissionRepository::class);
};
