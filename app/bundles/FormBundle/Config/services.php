<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

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

    $services->set(Mautic\FormBundle\Event\Service\FieldValueTransformer::class);

    $services->load('Mautic\\FormBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\FormBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);
    $services->set(Mautic\FormBundle\Form\Type\FieldType::class)
        ->call('setFieldModel', [service('mautic.form.model.field')])
        ->call('setFormModel', [service('mautic.form.model.form')]);
    $services->set(Mautic\FormBundle\Form\Type\SubmitActionEmailType::class)
        ->call('setFieldModel', [service('mautic.form.model.field')])
        ->call('setFormModel', [service('mautic.form.model.form')]);
    $services->set(Mautic\FormBundle\Form\Type\SubmitActionRepostType::class)
        ->call('setFieldModel', [service('mautic.form.model.field')])
        ->call('setFormModel', [service('mautic.form.model.form')]);

    $services->set(Mautic\FormBundle\Validator\Constraint\FileExtensionConstraintValidator::class)
        ->tag('validator.constraint_validator', ['alias' => 'file_extension_constraint_validator']);

    $services->alias('mautic.form.model.action', Mautic\FormBundle\Model\ActionModel::class);
    $services->alias('mautic.form.model.field', Mautic\FormBundle\Model\FieldModel::class);
    $services->alias('mautic.form.model.form', Mautic\FormBundle\Model\FormModel::class);
    $services->alias('mautic.form.model.submission', Mautic\FormBundle\Model\SubmissionModel::class);
    $services->alias('mautic.form.model.submission_result_loader', Mautic\FormBundle\Model\SubmissionResultLoader::class);
};
