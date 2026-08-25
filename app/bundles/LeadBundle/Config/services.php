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
    $services->set(Mautic\LeadBundle\Segment\Stat\SegmentDependencies::class);

    $services->set(Mautic\LeadBundle\Segment\Stat\SegmentChartQueryFactory::class);

    $services->set(Mautic\LeadBundle\Segment\Stat\SegmentCampaignShare::class);
    $services->set(Mautic\LeadBundle\Segment\Query\ContactSegmentQueryBuilder::class);
    $services->set(Mautic\LeadBundle\Segment\Decorator\DecoratorFactory::class);
    $services->set(Mautic\LeadBundle\Segment\Decorator\BaseDecorator::class);
    $services->set(Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator::class);
    $services->set(Mautic\LeadBundle\Segment\Decorator\CompanyDecorator::class);
    $services->set(Mautic\LeadBundle\Segment\Decorator\DateDecorator::class);
    $services->set(Mautic\LeadBundle\Segment\Decorator\Date\DateOptionFactory::class);
    $services->set(Mautic\LeadBundle\Segment\Decorator\Date\TimezoneResolver::class);
    $services->set('mautic.lead.query.builder.basic', Mautic\LeadBundle\Segment\Query\Filter\BaseFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.foreign.value', Mautic\LeadBundle\Segment\Query\Filter\ForeignValueFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.foreign.func', Mautic\LeadBundle\Segment\Query\Filter\ForeignFuncFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.special.dnc', Mautic\LeadBundle\Segment\Query\Filter\DoNotContactFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.special.integration', Mautic\LeadBundle\Segment\Query\Filter\IntegrationCampaignFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.special.sessions', Mautic\LeadBundle\Segment\Query\Filter\SessionsFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.complex_relation.value', Mautic\LeadBundle\Segment\Query\Filter\ComplexRelationValueFilterQueryBuilder::class);
    $services->set('mautic.lead.query.builder.channel_click.value', Mautic\LeadBundle\Segment\Query\Filter\ChannelClickQueryBuilder::class);
    $services->get(Mautic\LeadBundle\Entity\CompanyRepository::class)
        ->call('setUniqueIdentifiersOperator', ['%mautic.company_unique_identifiers_operator%']);
    $services->get(Mautic\LeadBundle\Entity\LeadRepository::class)
        ->call('setUniqueIdentifiersOperator', ['%mautic.contact_unique_identifiers_operator%']);

    $services->alias('mautic.lead.model.company_report_data', Mautic\LeadBundle\Model\CompanyReportData::class);
    $services->alias('mautic.lead.model.ipaddress', Mautic\LeadBundle\Model\IpAddressModel::class);
    $services->set(Mautic\LeadBundle\Security\Permissions\LeadPermissions::class);
};
