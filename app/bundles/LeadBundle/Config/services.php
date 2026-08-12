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
        'Form/Validator/Constraints/UniqueCustomField.php',
        'Validator/Constraints/SegmentDate.php',
    ];

    $services->load('Mautic\\LeadBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\LeadBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);
    $services->set(Mautic\LeadBundle\EventListener\SerializerSubscriber::class)->tag('jms_serializer.event_subscriber', ['event' => JMS\Serializer\EventDispatcher\Events::POST_SERIALIZE]);
    $services->set(Mautic\LeadBundle\Form\Validator\Constraints\FieldAliasKeywordValidator::class)->tag('validator.constraint_validator');
    $services->set(Mautic\CoreBundle\Form\Validator\Constraints\FileEncodingValidator::class)->tag('validator.constraint_validator');
    $services->set(Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccessValidator::class)->tag('validator.constraint_validator', ['alias' => 'leadlist_access']);
    $services->set(Mautic\LeadBundle\Form\Validator\Constraints\UniqueUserAliasValidator::class)->tag('validator.constraint_validator', ['alias' => 'uniqueleadlist']);
    $services->set(Mautic\LeadBundle\Form\Validator\Constraints\SegmentInUseValidator::class)->tag('validator.constraint_validator', ['alias' => 'segment_in_use']);
    $services->set(Mautic\LeadBundle\Twig\Helper\AvatarHelper::class)->tag('twig.helper', ['alias' => 'lead_avatar']);
    $services->set(Mautic\LeadBundle\Twig\Helper\DefaultAvatarHelper::class)->tag('twig.helper', ['alias' => 'default_avatar']);
    $services->set(Mautic\LeadBundle\Twig\Helper\DncReasonHelper::class)->tag('twig.helper', ['alias' => 'lead_dnc_reason']);
    $services->set(Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadClickData::class);
    $services->set(Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadDncData::class);
    $services->set(Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadTagData::class);
    $services->set(Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadPageHitData::class);
    $services->set(Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadSegmentsData::class);
    $services->set(Mautic\LeadBundle\Form\Validator\Constraints\EmailAddressValidator::class)->tag('validator.constraint_validator');
    $services->set(Mautic\LeadBundle\Validator\Constraints\SegmentUsedInCampaignsValidator::class)->tag('validator.constraint_validator');
    $services->set(Mautic\LeadBundle\Validator\Constraints\LengthValidator::class)->tag('validator.constraint_validator');
    $services->set(Mautic\LeadBundle\Segment\Stat\SegmentDependencies::class);

    $services->set(Mautic\LeadBundle\Segment\Stat\SegmentChartQueryFactory::class);

    $services->set(Mautic\LeadBundle\Segment\Stat\SegmentCampaignShare::class);
    $services->alias('mautic.lead.model.lead_segment_filter_factory', Mautic\LeadBundle\Segment\ContactSegmentFilterFactory::class);
    $services->set(Mautic\LeadBundle\Segment\Query\ContactSegmentQueryBuilder::class);
    $services->alias('mautic.lead.model.lead_segment_service', Mautic\LeadBundle\Segment\ContactSegmentService::class);
    $services->alias('mautic.lead.model.lead_segment_schema_cache', Mautic\LeadBundle\Segment\TableSchemaColumnsCache::class);
    $services->alias('mautic.lead.model.relative_date', Mautic\LeadBundle\Segment\RelativeDate::class);
    $services->alias('mautic.lead.model.lead_segment_filter_operator', Mautic\LeadBundle\Segment\ContactSegmentFilterOperator::class);
    $services->set(Mautic\LeadBundle\Segment\Decorator\DecoratorFactory::class);
    $services->alias('mautic.lead.model.lead_segment_decorator_factory', Mautic\LeadBundle\Segment\Decorator\DecoratorFactory::class);
    $services->set(Mautic\LeadBundle\Segment\Decorator\BaseDecorator::class);
    $services->alias('mautic.lead.model.lead_segment_decorator_base', Mautic\LeadBundle\Segment\Decorator\BaseDecorator::class);
    $services->set(Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator::class);
    $services->alias('mautic.lead.model.lead_segment_decorator_custom_mapped', Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator::class);
    $services->set(Mautic\LeadBundle\Segment\Decorator\CompanyDecorator::class);
    $services->alias('mautic.lead.model.lead_segment_decorator_company', Mautic\LeadBundle\Segment\Decorator\CompanyDecorator::class);
    $services->set(Mautic\LeadBundle\Segment\Decorator\DateDecorator::class);
    $services->alias('mautic.lead.model.lead_segment_decorator_date', Mautic\LeadBundle\Segment\Decorator\DateDecorator::class);
    $services->set(Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::class);
    $services->set(Mautic\LeadBundle\Segment\Decorator\Date\TimezoneResolver::class);
    $services->alias('mautic.lead.model.random_parameter_name', Mautic\LeadBundle\Segment\RandomParameterName::class);
    $services->set('mautic.lead.query.builder.basic', Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.foreign.value', Mautic\LeadBundle\Segment\Query\Filter\ForeignValueFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.foreign.func', Mautic\LeadBundle\Segment\Query\Filter\ForeignFuncFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.special.dnc', Mautic\LeadBundle\Segment\Query\Filter\DoNotContactFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.special.integration', Mautic\LeadBundle\Segment\Query\Filter\IntegrationCampaignFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.special.sessions', Mautic\LeadBundle\Segment\Query\Filter\SessionsFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.complex_relation.value', Mautic\LeadBundle\Segment\Query\Filter\ComplexRelationValueFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.channel_click.value', Mautic\LeadBundle\Segment\Query\Filter\ChannelClickQueryBuilder::class);
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
    $services->alias('mautic.lead.model.ipaddress', Mautic\LeadBundle\Model\IpAddressModel::class);
    $services->alias('mautic.lead.model.export_scheduler', Mautic\LeadBundle\Model\ContactExportSchedulerModel::class);
    $services->alias('mautic.lead.repository.list_lead', Mautic\LeadBundle\Entity\ListLeadRepository::class);
    $services->get(Mautic\LeadBundle\Validator\Constraints\SegmentDateValidator::class)->tag('validator.constraint_validator');
};
