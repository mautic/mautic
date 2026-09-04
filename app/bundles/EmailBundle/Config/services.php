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
        'Helper/DTO',
        'Model/AbTest/EmailStatus.php',
        'Enum',
    ];

    $services->load('Mautic\\EmailBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\EmailBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);
    $services->set('mautic.email.fixture.email', Mautic\EmailBundle\DataFixtures\ORM\LoadEmailData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);
    $services->alias(Mautic\EmailBundle\DataFixtures\ORM\LoadEmailData::class, 'mautic.email.fixture.email');
    $services->set('mautic.di.env_processor.mailerdsn', Mautic\EmailBundle\DependencyInjection\EnvProcessor\MailerDsnEnvVarProcessor::class)->tag('container.env_var_processor');
    $services->set('mautic.message.search.contact', Mautic\EmailBundle\MonitoredEmail\Search\ContactFinder::class);

    $services->set(Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscribe::class);
    $services->alias('mautic.message.processor.unsubscribe', Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscribe::class);

    $services->set(Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop::class);
    $services->alias('mautic.message.processor.feedbackloop', Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop::class);

    $services->set('mautic.validator.email', Mautic\EmailBundle\Helper\EmailValidator::class);
    $services->set('mautic.email.fetcher', Mautic\EmailBundle\MonitoredEmail\Fetcher::class);
    $services->set('mautic.email.helper.stats_collection', Mautic\EmailBundle\Helper\StatsCollectionHelper::class);
    $services->set('mautic.email.stats.helper_container', Mautic\EmailBundle\Stats\StatHelperContainer::class);

    $services->set('mautic.email.stats.helper_bounced', Mautic\EmailBundle\Stats\Helper\BouncedHelper::class)
        ->tag('mautic.email_stat_helper');
    $services->set('mautic.email.stats.helper_clicked', Mautic\EmailBundle\Stats\Helper\ClickedHelper::class)
        ->tag('mautic.email_stat_helper');
    $services->set('mautic.email.stats.helper_failed', Mautic\EmailBundle\Stats\Helper\FailedHelper::class)
        ->tag('mautic.email_stat_helper');
    $services->set('mautic.email.stats.helper_opened', Mautic\EmailBundle\Stats\Helper\OpenedHelper::class)
        ->tag('mautic.email_stat_helper');
    $services->set('mautic.email.stats.helper_sent', Mautic\EmailBundle\Stats\Helper\SentHelper::class)
        ->tag('mautic.email_stat_helper');
    $services->set('mautic.email.stats.helper_unsubscribed', Mautic\EmailBundle\Stats\Helper\UnsubscribedHelper::class)
        ->tag('mautic.email_stat_helper');

    $services->set('mautic.email.validator.multiple_emails_valid_validator', Mautic\EmailBundle\Validator\MultipleEmailsValidValidator::class)->tag('validator.constraint_validator');
    $services->set('mautic.email.validator.email_or_token_list_validator', Mautic\EmailBundle\Validator\EmailOrEmailTokenListValidator::class)->tag('validator.constraint_validator');

    $services->alias(Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface::class, Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProvider::class);
    $services->set(Mautic\EmailBundle\Mailer\Transport\TransportFactory::class)->decorate('mailer.transport_factory');

    $services->set(Mautic\EmailBundle\MonitoredEmail\Processor\Bounce::class);
    $services->set(Mautic\EmailBundle\MonitoredEmail\Processor\Reply::class);

    $services->alias('mautic.email.model.email', Mautic\EmailBundle\Model\EmailModel::class);
    $services->alias('mautic.email.model.send_email_to_user', Mautic\EmailBundle\Model\SendEmailToUser::class);
    $services->alias('mautic.email.model.send_email_to_contacts', Mautic\EmailBundle\Model\SendEmailToContact::class);
    $services->alias('mautic.email.model.transport_callback', Mautic\EmailBundle\Model\TransportCallback::class);
    $services->alias('mautic.email.repository.email', Mautic\EmailBundle\Entity\EmailRepository::class);
    $services->alias('mautic.email.repository.emailReply', Mautic\EmailBundle\Entity\EmailReplyRepository::class);
    $services->alias('mautic.email.repository.stat', Mautic\EmailBundle\Entity\StatRepository::class);
    $services->alias('mautic.helper.mailbox', Mautic\EmailBundle\MonitoredEmail\Mailbox::class);
    $services->alias('mautic.helper.mailer', Mautic\EmailBundle\Helper\MailHelper::class);
    $services->alias('mautic.message.processor.bounce', Mautic\EmailBundle\MonitoredEmail\Processor\Bounce::class);
    $services->alias('mautic.message.processor.replier', Mautic\EmailBundle\MonitoredEmail\Processor\Reply::class);
    $services->alias('mautic.email.helper.stat', Mautic\EmailBundle\Stat\StatHelper::class);
    $services->alias('mautic.email.stats.helper_container', Mautic\EmailBundle\Stats\StatHelperContainer::class);

    $services->get(Mautic\EmailBundle\EventListener\WebhookSubscriber::class)
        ->arg('$includeDetails', '%mautic.webhook_email_details%');
};
