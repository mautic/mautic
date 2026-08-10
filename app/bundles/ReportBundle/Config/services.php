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
        'Generator',
        'Builder/MauticReportBuilder.php',
        'Form/DataTransformer/ReportFilterDataTransformer.php',
        'Scheduler/Entity',
        'Scheduler/Option',
    ];

    $services->load('Mautic\\ReportBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\ReportBundle\\Entity\\', '../Entity/*Repository.php');
    $services->set('mautic.report.validator.schedule_is_valid_validator', Mautic\ReportBundle\Scheduler\Validator\ScheduleIsValidValidator::class)->tag('validator.constraint_validator');
    $services->set('mautic.report.model.scheduler_builder', Mautic\ReportBundle\Scheduler\Builder\SchedulerBuilder::class);
    $services->set('mautic.report.model.scheduler_template_factory', Mautic\ReportBundle\Scheduler\Factory\SchedulerTemplateFactory::class);
    $services->set('mautic.report.model.scheduler_date_builder', Mautic\ReportBundle\Scheduler\Date\DateBuilder::class);
    $services->set('mautic.report.model.scheduler_planner', Mautic\ReportBundle\Scheduler\Model\SchedulerPlanner::class);
    $services->set('mautic.report.model.send_schedule', Mautic\ReportBundle\Scheduler\Model\SendSchedule::class);
    $services->set('mautic.report.model.file_handler', Mautic\ReportBundle\Scheduler\Model\FileHandler::class);
    $services->set('mautic.report.model.message_schedule', Mautic\ReportBundle\Scheduler\Model\MessageSchedule::class);
    $services->set('mautic.report.model.report_data_adapter', Mautic\ReportBundle\Adapter\ReportDataAdapter::class);

    $services->alias('mautic.report.repository.scheduler', Mautic\ReportBundle\Entity\SchedulerRepository::class);

    $services->alias('mautic.report.model.report', Mautic\ReportBundle\Model\ReportModel::class);
    $services->alias('mautic.report.model.csv_exporter', Mautic\ReportBundle\Model\CsvExporter::class);
    $services->alias('mautic.report.model.excel_exporter', Mautic\ReportBundle\Model\ExcelExporter::class);
    $services->alias('mautic.report.model.report_exporter', Mautic\ReportBundle\Model\ReportExporter::class);
    $services->alias('mautic.report.model.schedule_model', Mautic\ReportBundle\Model\ScheduleModel::class);
    $services->alias('mautic.report.model.report_export_options', Mautic\ReportBundle\Model\ReportExportOptions::class);
    $services->alias('mautic.report.model.report_file_writer', Mautic\ReportBundle\Model\ReportFileWriter::class);
    $services->alias('mautic.report.model.export_handler', Mautic\ReportBundle\Model\ExportHandler::class);

    $services->set(Mautic\ReportBundle\Helper\ReportHelper::class)
        ->tag('twig.helper', ['alias' => 'report']);
    $services->set(Mautic\ReportBundle\Security\Permissions\ReportPermissions::class);
};
