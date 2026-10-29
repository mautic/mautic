<?php

declare(strict_types=1);

use Mautic\LeadBundle\EventListener\SerializerSubscriber;
use JMS\Serializer\EventDispatcher\Events;
use Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccessValidator;
use Mautic\LeadBundle\Form\Validator\Constraints\UniqueUserAliasValidator;
use Mautic\LeadBundle\Form\Validator\Constraints\SegmentInUseValidator;
use Mautic\LeadBundle\Twig\Helper\AvatarHelper;
use Mautic\LeadBundle\Twig\Helper\DefaultAvatarHelper;
use Mautic\LeadBundle\Twig\Helper\DncReasonHelper;
use Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadClickData;
use Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadDncData;
use Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadTagData;
use Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadPageHitData;
use Mautic\LeadBundle\Tests\DataFixtures\ORM\LoadSegmentsData;
use Mautic\LeadBundle\Segment\Stat\SegmentDependencies;
use Mautic\LeadBundle\Segment\Stat\SegmentChartQueryFactory;
use Mautic\LeadBundle\Segment\Stat\SegmentCampaignShare;
use Mautic\LeadBundle\Segment\Query\ContactSegmentQueryBuilder;
use Mautic\LeadBundle\Segment\Decorator\DecoratorFactory;
use Mautic\LeadBundle\Segment\Decorator\BaseDecorator;
use Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator;
use Mautic\LeadBundle\Segment\Decorator\CompanyDecorator;
use Mautic\LeadBundle\Segment\Decorator\DateDecorator;
use Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory;
use Mautic\LeadBundle\Segment\Decorator\Date\TimezoneResolver;
use Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\ForeignValueFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\ForeignFuncFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\DoNotContactFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\IntegrationCampaignFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\SessionsFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\ComplexRelationValueFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\ChannelClickQueryBuilder;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Model\IpAddressModel;
use Mautic\LeadBundle\Security\Permissions\LeadPermissions;
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

    $services->load('Mautic\\LeadBundle\\Entity\\', '../Entity/*Repository.php');
    $services->set(SerializerSubscriber::class)->tag('jms_serializer.event_subscriber', ['event' => Events::POST_SERIALIZE]);
    $services->set(LeadListAccessValidator::class)->tag('validator.constraint_validator', ['alias' => 'leadlist_access']);
    $services->set(UniqueUserAliasValidator::class)->tag('validator.constraint_validator', ['alias' => 'uniqueleadlist']);
    $services->set(SegmentInUseValidator::class)->tag('validator.constraint_validator', ['alias' => 'segment_in_use']);
    $services->set(AvatarHelper::class)->tag('twig.helper', ['alias' => 'lead_avatar']);
    $services->set(DefaultAvatarHelper::class)->tag('twig.helper', ['alias' => 'default_avatar']);
    $services->set(DncReasonHelper::class)->tag('twig.helper', ['alias' => 'lead_dnc_reason']);
    $services->set(LoadClickData::class);
    $services->set(LoadDncData::class);
    $services->set(LoadTagData::class);
    $services->set(LoadPageHitData::class);
    $services->set(LoadSegmentsData::class);
    $services->set(SegmentDependencies::class);

    $services->set(SegmentChartQueryFactory::class);

    $services->set(SegmentCampaignShare::class);
    $services->set(ContactSegmentQueryBuilder::class);
    $services->set(DecoratorFactory::class);
    $services->set(BaseDecorator::class);
    $services->set(CustomMappedDecorator::class);
    $services->set(CompanyDecorator::class);
    $services->set(DateDecorator::class);
    $services->set(DateOptionFactory::class);
    $services->set(TimezoneResolver::class);
    $services->set('mautic.lead.query.builder.basic', BaseFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.foreign.value', ForeignValueFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.foreign.func', ForeignFuncFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.special.dnc', DoNotContactFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.special.integration', IntegrationCampaignFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.special.sessions', SessionsFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.complex_relation.value', ComplexRelationValueFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.channel_click.value', ChannelClickQueryBuilder::class);
    $services->get(CompanyRepository::class)
        ->call('setUniqueIdentifiersOperator', ['%mautic.company_unique_identifiers_operator%']);
    $services->get(LeadRepository::class)
        ->call('setUniqueIdentifiersOperator', ['%mautic.contact_unique_identifiers_operator%']);

    $services->alias('mautic.lead.model.company_report_data', CompanyReportData::class);
    $services->alias('mautic.lead.model.ipaddress', IpAddressModel::class);
    $services->set(LeadPermissions::class);
};
