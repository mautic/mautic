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
    $services->set('mautic.lead.serializer.subscriber', Mautic\LeadBundle\EventListener\SerializerSubscriber::class)->tag('jms_serializer.event_subscriber', ['event' => JMS\Serializer\EventDispatcher\Events::POST_SERIALIZE]);
    $services->alias(Mautic\LeadBundle\EventListener\SerializerSubscriber::class, 'mautic.lead.serializer.subscriber');
    $services->set(Mautic\LeadBundle\Form\Validator\Constraints\FieldAliasKeywordValidator::class)->tag('validator.constraint_validator');
    $services->set(Mautic\CoreBundle\Form\Validator\Constraints\FileEncodingValidator::class)->tag('validator.constraint_validator');
    $services->set('mautic.validator.leadlistaccess', Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccessValidator::class)->tag('validator.constraint_validator', ['alias' => 'leadlist_access']);
    $services->alias(Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccessValidator::class, 'mautic.validator.leadlistaccess');
    $services->set('mautic.lead.constraint.alias', Mautic\LeadBundle\Form\Validator\Constraints\UniqueUserAliasValidator::class)->tag('validator.constraint_validator', ['alias' => 'uniqueleadlist']);
    $services->alias(Mautic\LeadBundle\Form\Validator\Constraints\UniqueUserAliasValidator::class, 'mautic.lead.constraint.alias');
    $services->set('mautic.lead_list.constraint.in_use', Mautic\LeadBundle\Form\Validator\Constraints\SegmentInUseValidator::class)->tag('validator.constraint_validator', ['alias' => 'segment_in_use']);
    $services->alias(Mautic\LeadBundle\Form\Validator\Constraints\SegmentInUseValidator::class, 'mautic.lead_list.constraint.in_use');
    $services->set('mautic.helper.twig.avatar', Mautic\LeadBundle\Twig\Helper\AvatarHelper::class)->tag('twig.helper', ['alias' => 'lead_avatar']);
    $services->alias(Mautic\LeadBundle\Twig\Helper\AvatarHelper::class, 'mautic.helper.twig.avatar');
    $services->set('mautic.helper.twig.default_avatar', Mautic\LeadBundle\Twig\Helper\DefaultAvatarHelper::class)->tag('twig.helper', ['alias' => 'default_avatar']);
    $services->alias(Mautic\LeadBundle\Twig\Helper\DefaultAvatarHelper::class, 'mautic.helper.twig.default_avatar');
    $services->set('mautic.helper.twig.dnc_reason', Mautic\LeadBundle\Twig\Helper\DncReasonHelper::class)->tag('twig.helper', ['alias' => 'lead_dnc_reason']);
    $services->alias(Mautic\LeadBundle\Twig\Helper\DncReasonHelper::class, 'mautic.helper.twig.dnc_reason');
    $services->set('mautic.lead.fixture.test.click', Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadClickData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->alias(Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadClickData::class, 'mautic.lead.fixture.test.click');
    $services->set('mautic.lead.fixture.test.dnc', Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadDncData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->alias(Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadDncData::class, 'mautic.lead.fixture.test.dnc');
    $services->set('mautic.lead.fixture.test.tag', Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadTagData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->alias(Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadTagData::class, 'mautic.lead.fixture.test.tag');
    $services->set('mautic.lead.fixture.test.page_hit', Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadPageHitData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->alias(Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadPageHitData::class, 'mautic.lead.fixture.test.page_hit');
    $services->set('mautic.lead.fixture.test.segment', Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadSegmentsData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->alias(Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadSegmentsData::class, 'mautic.lead.fixture.test.segment');
    $services->set('mautic.lead.fixture.company', Mautic\LeadBundle\DataFixtures\ORM\LoadCompanyData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->alias(Mautic\LeadBundle\DataFixtures\ORM\LoadCompanyData::class, 'mautic.lead.fixture.company');
    $services->set('mautic.lead.fixture.contact', Mautic\LeadBundle\DataFixtures\ORM\LoadLeadData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->alias(Mautic\LeadBundle\DataFixtures\ORM\LoadLeadData::class, 'mautic.lead.fixture.contact');
    $services->set('mautic.lead.fixture.segment', Mautic\LeadBundle\DataFixtures\ORM\LoadLeadListData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->alias(Mautic\LeadBundle\DataFixtures\ORM\LoadLeadListData::class, 'mautic.lead.fixture.segment');
    $services->set('mautic.lead.fixture.category', Mautic\LeadBundle\DataFixtures\ORM\LoadCategoryData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->alias(Mautic\LeadBundle\DataFixtures\ORM\LoadCategoryData::class, 'mautic.lead.fixture.category');
    $services->set('mautic.lead.fixture.categorizedleadlists', Mautic\LeadBundle\DataFixtures\ORM\LoadCategorizedLeadListData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->alias(Mautic\LeadBundle\DataFixtures\ORM\LoadCategorizedLeadListData::class, 'mautic.lead.fixture.categorizedleadlists');
    $services->set('mautic.lead.export_scheduled_audit_log_subscriber', Mautic\LeadBundle\EventListener\ContactExportSchedulerAuditLogSubscriber::class);
    $services->alias(Mautic\LeadBundle\EventListener\ContactExportSchedulerAuditLogSubscriber::class, 'mautic.lead.export_scheduled_audit_log_subscriber');
    $services->set('mautic.lead.export_scheduled_logger_subscriber', Mautic\LeadBundle\EventListener\ContactExportSchedulerLoggerSubscriber::class);
    $services->alias(Mautic\LeadBundle\EventListener\ContactExportSchedulerLoggerSubscriber::class, 'mautic.lead.export_scheduled_logger_subscriber');
    $services->set('mautic.lead.contact_scheduled_export.subscriber', Mautic\LeadBundle\EventListener\ContactScheduledExportSubscriber::class);
    $services->alias(Mautic\LeadBundle\EventListener\ContactScheduledExportSubscriber::class, 'mautic.lead.contact_scheduled_export.subscriber');
    $services->set('mautic.validator.emailaddress', Mautic\LeadBundle\Form\Validator\Constraints\EmailAddressValidator::class)->tag('validator.constraint_validator');
    $services->alias(Mautic\LeadBundle\Form\Validator\Constraints\EmailAddressValidator::class, 'mautic.validator.emailaddress');
    $services->set('mautic.lead.validator.custom_field', Mautic\LeadBundle\Validator\CustomFieldValidator::class);
    $services->alias(Mautic\LeadBundle\Validator\CustomFieldValidator::class, 'mautic.lead.validator.custom_field');
    $services->set('mautic.lead.validator.lead.list.campaign', Mautic\LeadBundle\Validator\SegmentUsedInCampaignsValidator::class);
    $services->alias(Mautic\LeadBundle\Validator\SegmentUsedInCampaignsValidator::class, 'mautic.lead.validator.lead.list.campaign');
    $services->set('mautic.lead.constraint.validator.lead.list.campaign', Mautic\LeadBundle\Validator\Constraints\SegmentUsedInCampaignsValidator::class)->tag('validator.constraint_validator');
    $services->alias(Mautic\LeadBundle\Validator\Constraints\SegmentUsedInCampaignsValidator::class, 'mautic.lead.constraint.validator.lead.list.campaign');
    $services->set('mautic.lead.event.dispatcher', Mautic\LeadBundle\Helper\LeadChangeEventDispatcher::class);
    $services->alias(Mautic\LeadBundle\Helper\LeadChangeEventDispatcher::class, 'mautic.lead.event.dispatcher');
    $services->set('mautic.lead.merger', Mautic\LeadBundle\Deduplicate\ContactMerger::class);
    $services->alias(Mautic\LeadBundle\Deduplicate\ContactMerger::class, 'mautic.lead.merger');
    $services->set('mautic.lead.deduper', Mautic\LeadBundle\Deduplicate\ContactDeduper::class);
    $services->alias(Mautic\LeadBundle\Deduplicate\ContactDeduper::class, 'mautic.lead.deduper');
    $services->set('mautic.lead.helper.primary_company', Mautic\LeadBundle\Helper\PrimaryCompanyHelper::class);
    $services->alias(Mautic\LeadBundle\Helper\PrimaryCompanyHelper::class, 'mautic.lead.helper.primary_company');
    $services->set('mautic.lead.validator.length', Mautic\LeadBundle\Validator\Constraints\LengthValidator::class)->tag('validator.constraint_validator');
    $services->alias(Mautic\LeadBundle\Validator\Constraints\LengthValidator::class, 'mautic.lead.validator.length');
    $services->set('mautic.lead.segment.stat.dependencies', Mautic\LeadBundle\Segment\Stat\SegmentDependencies::class);
    $services->alias(Mautic\LeadBundle\Segment\Stat\SegmentDependencies::class, 'mautic.lead.segment.stat.dependencies');

    $services->set(Mautic\LeadBundle\Segment\Stat\SegmentChartQueryFactory::class);

    $services->set('mautic.lead.segment.stat.campaign.share', Mautic\LeadBundle\Segment\Stat\SegmentCampaignShare::class);
    $services->alias(Mautic\LeadBundle\Segment\Stat\SegmentCampaignShare::class, 'mautic.lead.segment.stat.campaign.share');
    $services->set('mautic.lead.columns.dictionary', Mautic\LeadBundle\Services\ContactColumnsDictionary::class);
    $services->alias(Mautic\LeadBundle\Services\ContactColumnsDictionary::class, 'mautic.lead.columns.dictionary');
    $services->set('mautic.lead.model.lead_segment_filter_factory', Mautic\LeadBundle\Segment\ContactSegmentFilterFactory::class);
    $services->alias(Mautic\LeadBundle\Segment\ContactSegmentFilterFactory::class, 'mautic.lead.model.lead_segment_filter_factory');
    $services->set('mautic.tracker.device', Mautic\LeadBundle\Tracker\DeviceTracker::class);
    $services->alias(Mautic\LeadBundle\Tracker\DeviceTracker::class, 'mautic.tracker.device');
    $services->set('mautic.lead.field.custom_field_column', Mautic\LeadBundle\Field\CustomFieldColumn::class);
    $services->alias(Mautic\LeadBundle\Field\CustomFieldColumn::class, 'mautic.lead.field.custom_field_column');
    $services->set('mautic.lead.field.custom_field_index', Mautic\LeadBundle\Field\CustomFieldIndex::class);
    $services->alias(Mautic\LeadBundle\Field\CustomFieldIndex::class, 'mautic.lead.field.custom_field_index');
    $services->set('mautic.lead.repository.lead_segment_filter_descriptor', Mautic\LeadBundle\Services\ContactSegmentFilterDictionary::class);
    $services->alias(Mautic\LeadBundle\Services\ContactSegmentFilterDictionary::class, 'mautic.lead.repository.lead_segment_filter_descriptor');
    $services->set('mautic.lead.service.segment_dependency_tree_factory', Mautic\LeadBundle\Services\SegmentDependencyTreeFactory::class);
    $services->alias(Mautic\LeadBundle\Services\SegmentDependencyTreeFactory::class, 'mautic.lead.service.segment_dependency_tree_factory');
    $services->set('mautic.lead.repository.lead_segment_query_builder', Mautic\LeadBundle\Segment\Query\ContactSegmentQueryBuilder::class);
    $services->alias(Mautic\LeadBundle\Segment\Query\ContactSegmentQueryBuilder::class, 'mautic.lead.repository.lead_segment_query_builder');
    $services->set('mautic.lead.model.lead_segment_service', Mautic\LeadBundle\Segment\ContactSegmentService::class);
    $services->alias(Mautic\LeadBundle\Segment\ContactSegmentService::class, 'mautic.lead.model.lead_segment_service');
    $services->set('mautic.lead.model.lead_segment_schema_cache', Mautic\LeadBundle\Segment\TableSchemaColumnsCache::class);
    $services->alias(Mautic\LeadBundle\Segment\TableSchemaColumnsCache::class, 'mautic.lead.model.lead_segment_schema_cache');
    $services->set('mautic.lead.model.relative_date', Mautic\LeadBundle\Segment\RelativeDate::class);
    $services->alias(Mautic\LeadBundle\Segment\RelativeDate::class, 'mautic.lead.model.relative_date');
    $services->set('mautic.lead.model.lead_segment_filter_operator', Mautic\LeadBundle\Segment\ContactSegmentFilterOperator::class);
    $services->alias(Mautic\LeadBundle\Segment\ContactSegmentFilterOperator::class, 'mautic.lead.model.lead_segment_filter_operator');
    $services->set('mautic.lead.model.lead_segment_decorator_factory', Mautic\LeadBundle\Segment\Decorator\DecoratorFactory::class);
    $services->alias(Mautic\LeadBundle\Segment\Decorator\DecoratorFactory::class, 'mautic.lead.model.lead_segment_decorator_factory');
    $services->set('mautic.lead.model.lead_segment_decorator_base', Mautic\LeadBundle\Segment\Decorator\BaseDecorator::class);
    $services->alias(Mautic\LeadBundle\Segment\Decorator\BaseDecorator::class, 'mautic.lead.model.lead_segment_decorator_base');
    $services->set('mautic.lead.model.lead_segment_decorator_custom_mapped', Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator::class);
    $services->alias(Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator::class, 'mautic.lead.model.lead_segment_decorator_custom_mapped');
    $services->set('mautic.lead.model.lead_segment_decorator_company', Mautic\LeadBundle\Segment\Decorator\CompanyDecorator::class);
    $services->alias(Mautic\LeadBundle\Segment\Decorator\CompanyDecorator::class, 'mautic.lead.model.lead_segment_decorator_company');
    $services->set('mautic.lead.model.lead_segment_decorator_date', Mautic\LeadBundle\Segment\Decorator\DateDecorator::class);
    $services->alias(Mautic\LeadBundle\Segment\Decorator\DateDecorator::class, 'mautic.lead.model.lead_segment_decorator_date');
    $services->set('mautic.lead.model.lead_segment.decorator.date.optionFactory', Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::class);
    $services->alias(Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::class, 'mautic.lead.model.lead_segment.decorator.date.optionFactory');
    $services->set('mautic.lead.model.lead_segment.timezoneResolver', Mautic\LeadBundle\Segment\Decorator\Date\TimezoneResolver::class);
    $services->alias(Mautic\LeadBundle\Segment\Decorator\Date\TimezoneResolver::class, 'mautic.lead.model.lead_segment.timezoneResolver');
    $services->set('mautic.lead.provider.fillterOperator', Mautic\LeadBundle\Provider\FilterOperatorProvider::class);
    $services->alias(Mautic\LeadBundle\Provider\FilterOperatorProvider::class, 'mautic.lead.provider.fillterOperator');
    $services->set('mautic.lead.provider.typeOperator', Mautic\LeadBundle\Provider\TypeOperatorProvider::class);
    $services->alias(Mautic\LeadBundle\Provider\TypeOperatorProvider::class, 'mautic.lead.provider.typeOperator');
    $services->set('mautic.lead.provider.fieldChoices', Mautic\LeadBundle\Provider\FieldChoicesProvider::class);
    $services->alias(Mautic\LeadBundle\Provider\FieldChoicesProvider::class, 'mautic.lead.provider.fieldChoices');
    $services->set('mautic.lead.provider.formAdjustments', Mautic\LeadBundle\Provider\FormAdjustmentsProvider::class);
    $services->alias(Mautic\LeadBundle\Provider\FormAdjustmentsProvider::class, 'mautic.lead.provider.formAdjustments');
    $services->set('mautic.lead.model.random_parameter_name', Mautic\LeadBundle\Segment\RandomParameterName::class);
    $services->alias(Mautic\LeadBundle\Segment\RandomParameterName::class, 'mautic.lead.model.random_parameter_name');
    $services->set('mautic.lead.segment.operator_options', Mautic\LeadBundle\Segment\OperatorOptions::class);
    $services->alias(Mautic\LeadBundle\Segment\OperatorOptions::class, 'mautic.lead.segment.operator_options');
    $services->set('mautic.lead.reportbundle.fields_builder', Mautic\LeadBundle\Report\FieldsBuilder::class);
    $services->alias(Mautic\LeadBundle\Report\FieldsBuilder::class, 'mautic.lead.reportbundle.fields_builder');
    $services->set('mautic.lead.factory.device_detector_factory', Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory\DeviceDetectorFactory::class);
    $services->alias(Mautic\LeadBundle\Tracker\Factory\DeviceDetectorFactory\DeviceDetectorFactory::class, 'mautic.lead.factory.device_detector_factory');
    $services->set('mautic.lead.service.contact_tracking_service', Mautic\LeadBundle\Tracker\Service\ContactTrackingService\ContactTrackingService::class);
    $services->alias(Mautic\LeadBundle\Tracker\Service\ContactTrackingService\ContactTrackingService::class, 'mautic.lead.service.contact_tracking_service');
    $services->set('mautic.lead.service.device_creator_service', Mautic\LeadBundle\Tracker\Service\DeviceCreatorService\DeviceCreatorService::class);
    $services->alias(Mautic\LeadBundle\Tracker\Service\DeviceCreatorService\DeviceCreatorService::class, 'mautic.lead.service.device_creator_service');
    $services->set('mautic.lead.service.device_tracking_service', Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingService::class);
    $services->alias(Mautic\LeadBundle\Tracker\Service\DeviceTrackingService\DeviceTrackingService::class, 'mautic.lead.service.device_tracking_service');
    $services->set('mautic.lead.field.schema_definition', Mautic\LeadBundle\Field\SchemaDefinition::class);
    $services->alias(Mautic\LeadBundle\Field\SchemaDefinition::class, 'mautic.lead.field.schema_definition');
    $services->set('mautic.lead.field.dispatcher.field_save_dispatcher', Mautic\LeadBundle\Field\Dispatcher\FieldSaveDispatcher::class);
    $services->alias(Mautic\LeadBundle\Field\Dispatcher\FieldSaveDispatcher::class, 'mautic.lead.field.dispatcher.field_save_dispatcher');
    $services->set('mautic.lead.field.dispatcher.field_column_dispatcher', Mautic\LeadBundle\Field\Dispatcher\FieldColumnDispatcher::class);
    $services->alias(Mautic\LeadBundle\Field\Dispatcher\FieldColumnDispatcher::class, 'mautic.lead.field.dispatcher.field_column_dispatcher');
    $services->set('mautic.lead.field.dispatcher.field_column_background_dispatcher', Mautic\LeadBundle\Field\Dispatcher\FieldColumnBackgroundJobDispatcher::class);
    $services->alias(Mautic\LeadBundle\Field\Dispatcher\FieldColumnBackgroundJobDispatcher::class, 'mautic.lead.field.dispatcher.field_column_background_dispatcher');
    $services->set('mautic.lead.field.fields_with_unique_identifier', Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier::class);
    $services->alias(Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier::class, 'mautic.lead.field.fields_with_unique_identifier');
    $services->set('mautic.lead.field.field_list', Mautic\LeadBundle\Field\FieldList::class);
    $services->alias(Mautic\LeadBundle\Field\FieldList::class, 'mautic.lead.field.field_list');
    $services->set('mautic.lead.field.identifier_fields', Mautic\LeadBundle\Field\IdentifierFields::class);
    $services->alias(Mautic\LeadBundle\Field\IdentifierFields::class, 'mautic.lead.field.identifier_fields');
    $services->set('mautic.lead.field.lead_field_saver', Mautic\LeadBundle\Field\LeadFieldSaver::class);
    $services->alias(Mautic\LeadBundle\Field\LeadFieldSaver::class, 'mautic.lead.field.lead_field_saver');
    $services->set('mautic.lead.field.settings.background_settings', Mautic\LeadBundle\Field\Settings\BackgroundSettings::class);
    $services->alias(Mautic\LeadBundle\Field\Settings\BackgroundSettings::class, 'mautic.lead.field.settings.background_settings');
    $services->set('mautic.lead.field.notification.custom_field', Mautic\LeadBundle\Field\Notification\CustomFieldNotification::class);
    $services->alias(Mautic\LeadBundle\Field\Notification\CustomFieldNotification::class, 'mautic.lead.field.notification.custom_field');
    $services->set('mautic.lead.query.builder.basic', Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder::class);
    $services->alias(Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder::class, 'mautic.lead.query.builder.basic');
    $services->set('mautic.lead.query.builder.foreign.value', Mautic\LeadBundle\Segment\Query\Filter\ForeignValueFilterQueryBuilder::class);
    $services->alias(Mautic\LeadBundle\Segment\Query\Filter\ForeignValueFilterQueryBuilder::class, 'mautic.lead.query.builder.foreign.value');
    $services->set('mautic.lead.query.builder.foreign.func', Mautic\LeadBundle\Segment\Query\Filter\ForeignFuncFilterQueryBuilder::class);
    $services->alias(Mautic\LeadBundle\Segment\Query\Filter\ForeignFuncFilterQueryBuilder::class, 'mautic.lead.query.builder.foreign.func');
    $services->set('mautic.lead.query.builder.special.dnc', Mautic\LeadBundle\Segment\Query\Filter\DoNotContactFilterQueryBuilder::class);
    $services->alias(Mautic\LeadBundle\Segment\Query\Filter\DoNotContactFilterQueryBuilder::class, 'mautic.lead.query.builder.special.dnc');
    $services->set('mautic.lead.query.builder.special.integration', Mautic\LeadBundle\Segment\Query\Filter\IntegrationCampaignFilterQueryBuilder::class);
    $services->alias(Mautic\LeadBundle\Segment\Query\Filter\IntegrationCampaignFilterQueryBuilder::class, 'mautic.lead.query.builder.special.integration');
    $services->set('mautic.lead.query.builder.special.sessions', Mautic\LeadBundle\Segment\Query\Filter\SessionsFilterQueryBuilder::class);
    $services->alias(Mautic\LeadBundle\Segment\Query\Filter\SessionsFilterQueryBuilder::class, 'mautic.lead.query.builder.special.sessions');
    $services->set('mautic.lead.query.builder.complex_relation.value', Mautic\LeadBundle\Segment\Query\Filter\ComplexRelationValueFilterQueryBuilder::class);
    $services->alias(Mautic\LeadBundle\Segment\Query\Filter\ComplexRelationValueFilterQueryBuilder::class, 'mautic.lead.query.builder.complex_relation.value');
    $services->set('mautic.lead.query.builder.channel_click.value', Mautic\LeadBundle\Segment\Query\Filter\ChannelClickQueryBuilder::class);
    $services->alias(Mautic\LeadBundle\Segment\Query\Filter\ChannelClickQueryBuilder::class, 'mautic.lead.query.builder.channel_click.value');
    $services->set('mautic.helper.field.alias', Mautic\LeadBundle\Helper\FieldAliasHelper::class);
    $services->alias(Mautic\LeadBundle\Helper\FieldAliasHelper::class, 'mautic.helper.field.alias');
    $services->alias('mautic.lead.model.lead', Mautic\LeadBundle\Model\LeadModel::class);
    $services->alias('mautic.lead.model.field_group', Mautic\LeadBundle\Model\FieldGroupModel::class);
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
    $services->alias('mautic.lead.helper.dnc_formatter_helper', Mautic\LeadBundle\Helper\DncFormatterHelper::class);
    $services->alias('mautic.tracker.contact', Mautic\LeadBundle\Tracker\ContactTracker::class);
    $services->alias('mautic.lead.field.settings.background_service', Mautic\LeadBundle\Field\BackgroundService::class);
    $services->alias('mautic.lead.report.dnc_report_service', Mautic\LeadBundle\Report\DncReportService::class);
    $services->alias('mautic.helper.segment.count.cache', Mautic\LeadBundle\Helper\SegmentCountCacheHelper::class);
    $services->get(Mautic\LeadBundle\Validator\Constraints\SegmentDateValidator::class)->tag('validator.constraint_validator');
};
