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
        'OptionsAccessor',
        'MonitoredEmail/Accessor',
        'MonitoredEmail/Organizer',
        'MonitoredEmail/Processor',
        'Stat/Reference.php',
    ];

    $services->load('Mautic\\EmailBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\EmailBundle\\Entity\\', '../Entity/*Repository.php');

    $services->alias(\Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface::class, \Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProvider::class);
    $services->set(\Mautic\EmailBundle\Mailer\Transport\TransportFactory::class)
        ->decorate('mailer.transport_factory');

    $services->alias('mautic.email.model.email', \Mautic\EmailBundle\Model\EmailModel::class);
    $services->alias('mautic.email.model.send_email_to_user', \Mautic\EmailBundle\Model\SendEmailToUser::class);
    $services->alias('mautic.email.model.send_email_to_contacts', \Mautic\EmailBundle\Model\SendEmailToContact::class);
    $services->alias('mautic.email.model.transport_callback', \Mautic\EmailBundle\Model\TransportCallback::class);
    $services->alias('mautic.email.helper.request.storage', \Mautic\EmailBundle\Helper\RequestStorageHelper::class);  /** @phpstan-ignore-line as the service is deprecated */
};
