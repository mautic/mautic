<?php

declare(strict_types=1);

use Mautic\EmailBundle\DependencyInjection\EnvProcessor\MailerDsnEnvVarProcessor;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscribe;
use Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop;
use Mautic\EmailBundle\Stats\StatHelperContainer;
use Mautic\EmailBundle\Stats\Helper\BouncedHelper;
use Mautic\EmailBundle\Stats\Helper\ClickedHelper;
use Mautic\EmailBundle\Stats\Helper\FailedHelper;
use Mautic\EmailBundle\Stats\Helper\OpenedHelper;
use Mautic\EmailBundle\Stats\Helper\SentHelper;
use Mautic\EmailBundle\Stats\Helper\UnsubscribedHelper;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProvider;
use Mautic\EmailBundle\Mailer\Transport\TransportFactory;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce;
use Mautic\EmailBundle\MonitoredEmail\Processor\Reply;
use Mautic\EmailBundle\Model\SendEmailToUser;
use Mautic\EmailBundle\Model\SendEmailToContact;
use Mautic\EmailBundle\Model\TransportCallback;
use Mautic\EmailBundle\EventListener\WebhookSubscriber;
use Mautic\EmailBundle\Security\Permissions\EmailPermissions;
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
        'Helper/DTO',
        'Model/AbTest/EmailStatus.php',
    ];

    $services->load('Mautic\\EmailBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\EmailBundle\\Entity\\', '../Entity/*Repository.php');
    $services->set(MailerDsnEnvVarProcessor::class)->tag('container.env_var_processor');

    $services->set(Unsubscribe::class);

    $services->set(FeedbackLoop::class);

    $services->set('mautic.email.stats.helper_container', StatHelperContainer::class);

    $services->set(BouncedHelper::class)
        ->tag('mautic.email_stat_helper');
    $services->set(ClickedHelper::class)
        ->tag('mautic.email_stat_helper');
    $services->set(FailedHelper::class)
        ->tag('mautic.email_stat_helper');
    $services->set(OpenedHelper::class)
        ->tag('mautic.email_stat_helper');
    $services->set(SentHelper::class)
        ->tag('mautic.email_stat_helper');
    $services->set(UnsubscribedHelper::class)
        ->tag('mautic.email_stat_helper');

    $services->alias(GeneratedColumnsProviderInterface::class, GeneratedColumnsProvider::class);
    $services->set(TransportFactory::class)->decorate('mailer.transport_factory');

    $services->set(Bounce::class);
    $services->set(Reply::class);

    $services->alias('mautic.email.model.send_email_to_user', SendEmailToUser::class);
    $services->alias('mautic.email.model.send_email_to_contacts', SendEmailToContact::class);
    $services->alias('mautic.email.model.transport_callback', TransportCallback::class);
    $services->alias('mautic.email.stats.helper_container', StatHelperContainer::class);

    $services->get(WebhookSubscriber::class)
        ->arg('$includeDetails', '%mautic.webhook_email_details%');
    $services->set(EmailPermissions::class);
};
