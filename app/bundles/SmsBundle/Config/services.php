<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use Mautic\SmsBundle\Helper\SmsHelper;
use Mautic\SmsBundle\Integration\Twilio\TwilioCallback;
use Mautic\SmsBundle\Integration\Twilio\TwilioTransport;
use Mautic\SmsBundle\Integration\TwilioIntegration;
use Mautic\SmsBundle\Security\Permissions\SmsPermissions;
use Mautic\SmsBundle\Sms\TransportChain;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $excludes = ['Helper/DTO', 'Collection'];

    $services->load('Mautic\\SmsBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\SmsBundle\\Entity\\', '../Entity/*Repository.php');

    $services->set(TwilioTransport::class)
        ->arg('$logger', service('monolog.logger.mautic'))
        ->tag('mautic.sms_transport', ['integrationAlias' => 'Twilio']);

    $services->alias('mautic.sms.twilio.transport', TwilioTransport::class);
    $services->set(SmsHelper::class)->tag('twig.helper', ['alias' => 'sms_helper']);

    $services->set(TransportChain::class)
        ->arg('$primaryTransport', param('mautic.sms_transport'));

    $services->set(TwilioCallback::class)->tag('mautic.sms_callback_handler');
    $services->set('mautic.integration.twilio', TwilioIntegration::class);

    $services->set(SmsPermissions::class);
};
