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
        'Deduplicate/Exception',
        'Field/DTO',
        'Field/Event',
        'Segment/ContactSegmentFilter.php',
        'Segment/ContactSegmentFilterCrate.php',
        'Segment/Decorator',
        'Segment/DoNotContact',
        'Segment/IntegrationCampaign',
        'Segment/Query',
        'Segment/Stat',
    ];

    $services->load('Mautic\\LeadBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\LeadBundle\\Entity\\', '../Entity/*Repository.php');
    $services->alias('mautic.lead.model.lead', \Mautic\LeadBundle\Model\LeadModel::class);

    $services->alias('mautic.lead.model.field', \Mautic\LeadBundle\Model\FieldModel::class);
    $services->alias('mautic.lead.model.list', \Mautic\LeadBundle\Model\ListModel::class);
    $services->alias('mautic.lead.model.note', \Mautic\LeadBundle\Model\NoteModel::class);
    $services->alias('mautic.lead.model.device', \Mautic\LeadBundle\Model\DeviceModel::class);
    $services->alias('mautic.lead.model.company', \Mautic\LeadBundle\Model\CompanyModel::class);
    $services->alias('mautic.lead.model.import', \Mautic\LeadBundle\Model\ImportModel::class);
    $services->alias('mautic.lead.model.tag', \Mautic\LeadBundle\Model\TagModel::class);
    $services->alias('mautic.lead.model.company_report_data', \Mautic\LeadBundle\Model\CompanyReportData::class);
    $services->alias('mautic.lead.model.dnc', \Mautic\LeadBundle\Model\DoNotContact::class);
    $services->alias('mautic.lead.model.segment.action', \Mautic\LeadBundle\Model\SegmentActionModel::class);
    $services->alias('mautic.lead.model.ipaddress', \Mautic\LeadBundle\Model\IpAddressModel::class);
    $services->alias('mautic.lead.model.export_scheduler', \Mautic\LeadBundle\Model\ContactExportSchedulerModel::class);
    $services->get(\Mautic\LeadBundle\Validator\Constraints\SegmentDateValidator::class)->tag('validator.constraint_validator');
};
