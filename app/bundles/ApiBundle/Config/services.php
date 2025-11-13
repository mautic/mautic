<?php

declare(strict_types=1);

use FOS\OAuthServerBundle\Form\Handler\AuthorizeFormHandler;
use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = [
        'Serializer/Exclusion',
        'Helper/BatchIdToEntityHelper.php',
    ];

    $services->load('Mautic\\ApiBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\ApiBundle\\Entity\\oAuth2\\', '../Entity/oAuth2/*Repository.php');

    $services->alias(AuthorizeFormHandler::class, 'fos_oauth_server.authorize.form.handler.default');

    $services->get(Mautic\ApiBundle\Controller\oAuth2\AuthorizeController::class)
        ->arg('$authorizeForm', service('fos_oauth_server.authorize.form'))
        ->arg('$oAuth2Server', service('fos_oauth_server.server'))
        ->arg('$clientManager', service('fos_oauth_server.client_manager.default'))
        ->tag('controller.service_arguments');

    $services->alias('mautic.api.model.client', Mautic\ApiBundle\Model\ClientModel::class);

    // Register custom PUT processor to fix PUT operations globally
    // This ensures PUT requests update existing entities instead of creating new ones
    // This decorates the default persist processor so it applies to all entities automatically
    $services->set(Mautic\ApiBundle\State\PutProcessor::class)
        ->decorate('api_platform.doctrine.orm.state.persist_processor')
        ->args([
            service('.inner'),
            service('doctrine.orm.entity_manager'),
        ]);
};
