<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Mautic\ReportBundle\Adapter\ReportDataAdapter;
use Mautic\ReportBundle\Helper\ReportHelper;
use Mautic\ReportBundle\Model\CsvExporter;
use Mautic\ReportBundle\Model\ExcelExporter;
use Mautic\ReportBundle\Model\ExportHandler;
use Mautic\ReportBundle\Model\ReportExporter;
use Mautic\ReportBundle\Model\ReportExportOptions;
use Mautic\ReportBundle\Model\ReportFileWriter;
use Mautic\ReportBundle\Model\ScheduleModel;
use Mautic\ReportBundle\Scheduler\Builder\SchedulerBuilder;
use Mautic\ReportBundle\Scheduler\Date\DateBuilder;
use Mautic\ReportBundle\Scheduler\Factory\SchedulerTemplateFactory;
use Mautic\ReportBundle\Scheduler\Model\FileHandler;
use Mautic\ReportBundle\Scheduler\Model\MessageSchedule;
use Mautic\ReportBundle\Scheduler\Model\SchedulerPlanner;
use Mautic\ReportBundle\Scheduler\Model\SendSchedule;
use Mautic\ReportBundle\Security\Permissions\ReportPermissions;
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
    $services->set('mautic.report.model.scheduler_builder', SchedulerBuilder::class);
    $services->set('mautic.report.model.scheduler_template_factory', SchedulerTemplateFactory::class);
    $services->set('mautic.report.model.scheduler_date_builder', DateBuilder::class);
    $services->set('mautic.report.model.scheduler_planner', SchedulerPlanner::class);
    $services->set('mautic.report.model.send_schedule', SendSchedule::class);
    $services->set('mautic.report.model.file_handler', FileHandler::class);
    $services->set('mautic.report.model.message_schedule', MessageSchedule::class);
    $services->set('mautic.report.model.report_data_adapter', ReportDataAdapter::class);

    $services->alias('mautic.report.model.csv_exporter', CsvExporter::class);
    $services->alias('mautic.report.model.excel_exporter', ExcelExporter::class);
    $services->alias('mautic.report.model.report_exporter', ReportExporter::class);
    $services->alias('mautic.report.model.schedule_model', ScheduleModel::class);
    $services->alias('mautic.report.model.report_export_options', ReportExportOptions::class);
    $services->alias('mautic.report.model.report_file_writer', ReportFileWriter::class);
    $services->alias('mautic.report.model.export_handler', ExportHandler::class);

    $services->set(ReportHelper::class)
        ->tag('twig.helper', ['alias' => 'report']);
    $services->set(ReportPermissions::class);
};
