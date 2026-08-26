<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
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

    $services->load('Mautic\\SmsBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);

    $services->set(Mautic\SmsBundle\Integration\Twilio\TwilioTransport::class)
        ->arg('$logger', service('monolog.logger.mautic'))
        ->tag('mautic.sms_transport');

    $services->alias('mautic.sms.twilio.transport', Mautic\SmsBundle\Integration\Twilio\TwilioTransport::class);
    $services->set(Mautic\SmsBundle\Helper\SmsHelper::class)->tag('twig.helper', ['alias' => 'sms_helper']);

    $services->set(Mautic\SmsBundle\Sms\TransportChain::class)
        ->arg('$primaryTransport', param('mautic.sms_transport'));

    $services->set(Mautic\SmsBundle\Integration\Twilio\TwilioCallback::class)->tag('mautic.sms_callback_handler');
    $services->set('mautic.integration.twilio', Mautic\SmsBundle\Integration\TwilioIntegration::class);

    $services->set(Mautic\SmsBundle\Security\Permissions\SmsPermissions::class);
};
