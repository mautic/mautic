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

    $services->set('mautic.sms.twilio.transport', Mautic\SmsBundle\Integration\Twilio\TwilioTransport::class)
        ->arg('$logger', service('monolog.logger.mautic'))
        ->tag('mautic.sms_transport', ['integrationAlias' => 'Twilio']);

    $services->alias(Mautic\SmsBundle\Integration\Twilio\TwilioTransport::class, 'mautic.sms.twilio.transport');
    $services->alias('mautic.sms.api', 'mautic.sms.twilio.transport');
    $services->set('mautic.helper.sms', Mautic\SmsBundle\Helper\SmsHelper::class)->tag('twig.helper', ['alias' => 'sms_helper']);
    $services->alias(Mautic\SmsBundle\Helper\SmsHelper::class, 'mautic.helper.sms');
    $services->set('mautic.sms.transport_chain', Mautic\SmsBundle\Sms\TransportChain::class)
        ->arg('$primaryTransport', param('mautic.sms_transport'));
    $services->alias(Mautic\SmsBundle\Sms\TransportChain::class, 'mautic.sms.transport_chain');
    $services->set('mautic.sms.helper.contact', Mautic\SmsBundle\Helper\ContactHelper::class);
    $services->set('mautic.sms.helper.reply', Mautic\SmsBundle\Helper\ReplyHelper::class);
    $services->set('mautic.sms.twilio.configuration', Mautic\SmsBundle\Integration\Twilio\Configuration::class);
    $services->set('mautic.sms.twilio.callback', Mautic\SmsBundle\Integration\Twilio\TwilioCallback::class)->tag('mautic.sms_callback_handler');
    $services->set('mautic.sms.broadcast.executioner', Mautic\SmsBundle\Broadcast\BroadcastExecutioner::class);
    $services->set('mautic.sms.broadcast.query', Mautic\SmsBundle\Broadcast\BroadcastQuery::class);
    $services->set('mautic.integration.twilio', Mautic\SmsBundle\Integration\TwilioIntegration::class);

    $services->alias('mautic.sms.model.sms', Mautic\SmsBundle\Model\SmsModel::class);
    $services->alias('mautic.sms.callback_handler_container', Mautic\SmsBundle\Callback\HandlerContainer::class);
};
