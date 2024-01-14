<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Mautic\CoreBundle\Helper\ServiceDeprecator;
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

    $deprecator = new ServiceDeprecator($services);
    $deprecator->setDeprecatedService(Mautic\ApiBundle\Helper\EntityResultHelper::class, 'Use as `new EntityResultHelper()` instead of a service.');
    $deprecator->setDeprecatedAlias('mautic.api.model.client', Mautic\ApiBundle\Model\ClientModel::class);
    $deprecator->setDeprecatedAlias('mautic.api.helper.entity_result', Mautic\ApiBundle\Helper\EntityResultHelper::class);
    $deprecator->setDeprecatedAlias('mautic.validator.oauthcallback', Mautic\ApiBundle\Form\Validator\Constraints\OAuthCallbackValidator::class);
};
