<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Mautic\LeadBundle\Controller\Api\LeadApiController;
use Mautic\LeadBundle\Controller\LeadController;
use Mautic\LeadBundle\Model\DoNotContact;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $configurator) {
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

    $services->set(LeadController::class)
        ->arg('$doNotContactModel', ref(DoNotContact::class))
        ->call('setContainer', [ref('service_container')]);

    $services->set(LeadApiController::class)
        ->arg('$doNotContactModel', ref(DoNotContact::class))
        ->call('setContainer', [ref('service_container')]);

    $services->load('Mautic\\LeadBundle\\Entity\\', '../Entity/*Repository.php');
    $services->alias('mautic.lead.model.lead', \Mautic\LeadBundle\Model\LeadModel::class);
};
