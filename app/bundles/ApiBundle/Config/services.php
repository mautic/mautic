<?php

declare(strict_types=1);

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
        'Serializer/Driver',
        'Serializer/Exclusion',
        'Helper/BatchIdToEntityHelper.php',
    ];

    $services->load('Mautic\\ApiBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\ApiBundle\\Entity\\oAuth2\\', '../Entity/oAuth2/*Repository.php');

    $services->get(\Mautic\ApiBundle\Controller\oAuth2\AuthorizeController::class)
        ->arg('$authorizeForm', service('fos_oauth_server.authorize.form'))
        ->arg('$authorizeFormHandler', service('fos_oauth_server.authorize.form.handler.default'))
        ->arg('$oAuth2Server', service('fos_oauth_server.server'))
        ->arg('$clientManager', service('fos_oauth_server.client_manager.default'));

    $services->get(Mautic\ApiBundle\EventListener\PreAuthorizationEventListener::class)
        ->tag(
            'kernel.event_listener',
            [
                'event'  => 'fos_oauth_server.pre_authorization_process',
                'method' => 'onPreAuthorizationProcess',
            ]
        )
        ->tag(
            'kernel.event_listener',
            [
                'event'  => 'fos_oauth_server.post_authorization_process',
                'method' => 'onPostAuthorizationProcess',
            ]
        );

    $services->get(Mautic\ApiBundle\Form\Validator\Constraints\OAuthCallbackValidator::class)
        ->tag('validator.constraint_validator');

    $services->get(Mautic\ApiBundle\Helper\EntityResultHelper::class)
        ->deprecate('mautic/mautic', '5.1.0', 'Use as `new EntityResultHelper()` instead of a service.');

    $services->alias('mautic.api.model.client', Mautic\ApiBundle\Model\ClientModel::class);
    $services->alias('mautic.api.helper.entity_result', Mautic\ApiBundle\Helper\EntityResultHelper::class);
    $services->alias('mautic.validator.oauthcallback', Mautic\ApiBundle\Form\Validator\Constraints\OAuthCallbackValidator::class);
};
