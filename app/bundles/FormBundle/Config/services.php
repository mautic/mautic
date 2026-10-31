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
    $services->set('mautic.form.type.field', Mautic\FormBundle\Form\Type\FieldType::class)
        ->call('setFieldModel', [service('mautic.form.model.field')])
        ->call('setFormModel', [service('mautic.form.model.form')]);
    $services->alias(Mautic\FormBundle\Form\Type\FieldType::class, 'mautic.form.type.field');
    $services->set('mautic.form.type.form_submitaction_sendemail', Mautic\FormBundle\Form\Type\SubmitActionEmailType::class)
        ->call('setFieldModel', [service('mautic.form.model.field')])
        ->call('setFormModel', [service('mautic.form.model.form')]);
    $services->alias(Mautic\FormBundle\Form\Type\SubmitActionEmailType::class, 'mautic.form.type.form_submitaction_sendemail');
    $services->set('mautic.form.type.form_submitaction_repost', Mautic\FormBundle\Form\Type\SubmitActionRepostType::class)
        ->call('setFieldModel', [service('mautic.form.model.field')])
        ->call('setFormModel', [service('mautic.form.model.form')]);
    $services->alias(Mautic\FormBundle\Form\Type\SubmitActionRepostType::class, 'mautic.form.type.form_submitaction_repost');
    $services->set('mautic.form.fixture.form', Mautic\FormBundle\DataFixtures\ORM\LoadFormData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->alias(Mautic\FormBundle\DataFixtures\ORM\LoadFormData::class, 'mautic.form.fixture.form');
    $services->set('mautic.form.fixture.form_result', Mautic\FormBundle\DataFixtures\ORM\LoadFormResultData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->alias(Mautic\FormBundle\DataFixtures\ORM\LoadFormResultData::class, 'mautic.form.fixture.form_result');
    $services->set('mautic.form.collector.object', Mautic\FormBundle\Collector\ObjectCollector::class);
    $services->alias(Mautic\FormBundle\Collector\ObjectCollector::class, 'mautic.form.collector.object');
    $services->set('mautic.form.collector.field', Mautic\FormBundle\Collector\FieldCollector::class);
    $services->alias(Mautic\FormBundle\Collector\FieldCollector::class, 'mautic.form.collector.field');
    $services->set('mautic.form.collector.mapped.object', Mautic\FormBundle\Collector\MappedObjectCollector::class);
    $services->alias(Mautic\FormBundle\Collector\MappedObjectCollector::class, 'mautic.form.collector.mapped.object');
    $services->set('mautic.form.collector.already.mapped.field', Mautic\FormBundle\Collector\AlreadyMappedFieldCollector::class);
    $services->alias(Mautic\FormBundle\Collector\AlreadyMappedFieldCollector::class, 'mautic.form.collector.already.mapped.field');
    $services->set('mautic.helper.form.field_helper', Mautic\FormBundle\Helper\FormFieldHelper::class);
    $services->alias(Mautic\FormBundle\Helper\FormFieldHelper::class, 'mautic.helper.form.field_helper');
    $services->set('mautic.form.helper.form_uploader', Mautic\FormBundle\Helper\FormUploader::class);
    $services->alias(Mautic\FormBundle\Helper\FormUploader::class, 'mautic.form.helper.form_uploader');
    $services->set('mautic.form.helper.token', Mautic\FormBundle\Helper\TokenHelper::class);
    $services->alias(Mautic\FormBundle\Helper\TokenHelper::class, 'mautic.form.helper.token');

    $services->set('mautic.form.helper.properties.accessor', Mautic\FormBundle\Helper\PropertiesAccessor::class);
    $services->alias(Mautic\FormBundle\Helper\PropertiesAccessor::class, 'mautic.form.helper.properties.accessor');
    $services->set('mautic.form.validator.upload_field_validator', Mautic\FormBundle\Validator\UploadFieldValidator::class);
    $services->alias(Mautic\FormBundle\Validator\UploadFieldValidator::class, 'mautic.form.validator.upload_field_validator');

    $services->set(Mautic\FormBundle\Validator\Constraint\FileExtensionConstraintValidator::class)
        ->tag('validator.constraint_validator', ['alias' => 'file_extension_constraint_validator']);

    $services->set('mautic.form.command.form_submissions_records_clean', Mautic\FormBundle\Command\DeleteOrphanSubmissionRecordsFromFormResultsTableCommand::class)->tag('console.command');
    $services->alias(Mautic\FormBundle\Command\DeleteOrphanSubmissionRecordsFromFormResultsTableCommand::class, 'mautic.form.command.form_submissions_records_clean');
    $services->set('mautic.form.command.form_submissions_table_clean', Mautic\FormBundle\Command\DeleteOrphanFormResultsTableCommand::class)->tag('console.command');
    $services->alias(Mautic\FormBundle\Command\DeleteOrphanFormResultsTableCommand::class, 'mautic.form.command.form_submissions_table_clean');

    $services->alias('mautic.form.model.action', Mautic\FormBundle\Model\ActionModel::class);
    $services->alias('mautic.form.model.field', Mautic\FormBundle\Model\FieldModel::class);
    $services->alias('mautic.form.model.form', Mautic\FormBundle\Model\FormModel::class);
    $services->alias('mautic.form.model.submission', Mautic\FormBundle\Model\SubmissionModel::class);
    $services->alias('mautic.form.model.submission_result_loader', Mautic\FormBundle\Model\SubmissionResultLoader::class);
    $services->alias('mautic.form.repository.form', Mautic\FormBundle\Entity\FormRepository::class);
    $services->alias('mautic.form.repository.submission', Mautic\FormBundle\Entity\SubmissionRepository::class);
};
