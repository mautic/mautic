<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'Serializer/Driver',
        'Serializer/Exclusion',
        'Helper/BatchIdToEntityHelper.php',
    ];

    $services->load('Mautic\\ApiBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\ApiBundle\\Entity\\oAuth2\\', '../Entity/oAuth2/*Repository.php');

    $services->get(\Mautic\ApiBundle\Controller\oAuth2\AuthorizeController::class)
        ->arg('$authorizeForm', ref('fos_oauth_server.authorize.form'))
        ->arg('$authorizeFormHandler', ref('fos_oauth_server.authorize.form.handler.default'))
        ->arg('$oAuth2Server', ref('fos_oauth_server.server'))
        ->arg('$clientManager', ref('fos_oauth_server.client_manager.default'));

    $services->alias('mautic.api.model.client', \Mautic\ApiBundle\Model\ClientModel::class);
};
