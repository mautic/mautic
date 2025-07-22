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

    $services->load('Mautic\\LeadBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);
    $services->alias('mautic.lead.model.lead', Mautic\LeadBundle\Model\LeadModel::class);
    $services->get(Mautic\LeadBundle\Entity\CompanyRepository::class)
        ->call('setUniqueIdentifiersOperator', ['%mautic.company_unique_identifiers_operator%']);
    $services->get(Mautic\LeadBundle\Entity\LeadRepository::class)
        ->call('setUniqueIdentifiersOperator', ['%mautic.contact_unique_identifiers_operator%'])
        ->call('setListLeadRepository', [\Symfony\Component\DependencyInjection\Loader\Configurator\service('mautic.lead.repository.list_lead')]);

    $services->alias('mautic.lead.model.field', Mautic\LeadBundle\Model\FieldModel::class);
    $services->alias('mautic.lead.model.list', Mautic\LeadBundle\Model\ListModel::class);
    $services->alias('mautic.lead.model.note', Mautic\LeadBundle\Model\NoteModel::class);
    $services->alias('mautic.lead.model.device', Mautic\LeadBundle\Model\DeviceModel::class);
    $services->alias('mautic.lead.model.company', Mautic\LeadBundle\Model\CompanyModel::class);
    $services->alias('mautic.lead.model.import', Mautic\LeadBundle\Model\ImportModel::class);
    $services->alias('mautic.lead.model.tag', Mautic\LeadBundle\Model\TagModel::class);
    $services->alias('mautic.lead.model.company_report_data', Mautic\LeadBundle\Model\CompanyReportData::class);
    $services->alias('mautic.lead.model.dnc', Mautic\LeadBundle\Model\DoNotContact::class);
    $services->alias('mautic.lead.model.segment.action', Mautic\LeadBundle\Model\SegmentActionModel::class);
    $services->alias('mautic.lead.model.ipaddress', Mautic\LeadBundle\Model\IpAddressModel::class);
    $services->alias('mautic.lead.model.export_scheduler', Mautic\LeadBundle\Model\ContactExportSchedulerModel::class);
    $services->alias('mautic.lead.repository.company', Mautic\LeadBundle\Entity\CompanyRepository::class);
    $services->alias('mautic.lead.repository.company_lead', Mautic\LeadBundle\Entity\CompanyLeadRepository::class);
    $services->alias('mautic.lead.repository.stages_lead_log', Mautic\LeadBundle\Entity\StagesChangeLogRepository::class);
    $services->alias('mautic.lead.repository.dnc', Mautic\LeadBundle\Entity\DoNotContactRepository::class);
    $services->alias('mautic.lead.repository.lead', Mautic\LeadBundle\Entity\LeadRepository::class);
    $services->alias('mautic.lead.repository.list_lead', Mautic\LeadBundle\Entity\ListLeadRepository::class);
    $services->alias('mautic.lead.repository.frequency_rule', Mautic\LeadBundle\Entity\FrequencyRuleRepository::class);
    $services->alias('mautic.lead.repository.lead_event_log', Mautic\LeadBundle\Entity\LeadEventLogRepository::class);
    $services->alias('mautic.lead.repository.lead_device', Mautic\LeadBundle\Entity\LeadDeviceRepository::class);
    $services->alias('mautic.lead.repository.lead_list', Mautic\LeadBundle\Entity\LeadListRepository::class);
    $services->alias('mautic.lead.repository.points_change_log', Mautic\LeadBundle\Entity\PointsChangeLogRepository::class);
    $services->alias('mautic.lead.repository.merged_records', Mautic\LeadBundle\Entity\MergeRecordRepository::class);
    $services->alias('mautic.lead.repository.field', Mautic\LeadBundle\Entity\LeadFieldRepository::class);
    $services->alias('mautic.company.deduper', Mautic\LeadBundle\Deduplicate\CompanyDeduper::class);
    $services->alias('mautic.lead.helper.contact_request_helper', Mautic\LeadBundle\Helper\ContactRequestHelper::class);
    $services->alias('mautic.tracker.contact', Mautic\LeadBundle\Tracker\ContactTracker::class);
    $services->get(Mautic\LeadBundle\Validator\Constraints\SegmentDateValidator::class)->tag('validator.constraint_validator');
};
