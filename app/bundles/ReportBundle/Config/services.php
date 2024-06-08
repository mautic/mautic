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

    $services->alias('mautic.report.model.report', Mautic\ReportBundle\Model\ReportModel::class);
    $services->alias('mautic.report.model.csv_exporter', Mautic\ReportBundle\Model\CsvExporter::class);
    $services->alias('mautic.report.model.excel_exporter', Mautic\ReportBundle\Model\ExcelExporter::class);
    $services->alias('mautic.report.model.report_exporter', Mautic\ReportBundle\Model\ReportExporter::class);
    $services->alias('mautic.report.model.schedule_model', Mautic\ReportBundle\Model\ScheduleModel::class);
    $services->alias('mautic.report.model.report_export_options', Mautic\ReportBundle\Model\ReportExportOptions::class);
    $services->alias('mautic.report.model.report_file_writer', Mautic\ReportBundle\Model\ReportFileWriter::class);
    $services->alias('mautic.report.model.export_handler', Mautic\ReportBundle\Model\ExportHandler::class);
};
