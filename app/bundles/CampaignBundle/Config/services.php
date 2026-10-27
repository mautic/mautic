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
        'EventCollector/Accessor',
        'Executioner/ContactFinder/Limiter/ContactLimiter.php',
        'Executioner/Dispatcher/Exception',
        'Executioner/Scheduler/Mode/DAO',
        'Membership/Exception',
    ];

    $services->load('Mautic\\CampaignBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->load('Mautic\\CampaignBundle\\Entity\\', '../Entity/*Repository.php')
        ->tag(Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass::REPOSITORY_SERVICE_TAG);

    $services->set(Mautic\CampaignBundle\DataFixtures\ORM\CampaignData::class)->tag(Doctrine\Bundle\FixturesBundle\DependencyInjection\CompilerPass\FixturesCompilerPass::FIXTURE_TAG);

    $services->set('mautic.campaign.contact_finder.kickoff', Mautic\CampaignBundle\Executioner\ContactFinder\KickoffContactFinder::class);
    $services->set('mautic.campaign.contact_finder.scheduled', Mautic\CampaignBundle\Executioner\ContactFinder\ScheduledContactFinder::class);
    $services->set('mautic.campaign.contact_finder.inactive', Mautic\CampaignBundle\Executioner\ContactFinder\InactiveContactFinder::class);
    $services->set('mautic.campaign.dispatcher.action', Mautic\CampaignBundle\Executioner\Dispatcher\ActionDispatcher::class);
    $services->set('mautic.campaign.dispatcher.condition', Mautic\CampaignBundle\Executioner\Dispatcher\ConditionDispatcher::class);
    $services->set('mautic.campaign.dispatcher.decision', Mautic\CampaignBundle\Executioner\Dispatcher\DecisionDispatcher::class);
    $services->set('mautic.campaign.scheduler.datetime', Mautic\CampaignBundle\Executioner\Scheduler\Mode\DateTime::class);
    $services->set('mautic.campaign.scheduler.interval', Mautic\CampaignBundle\Executioner\Scheduler\Mode\Interval::class);
    $services->set('mautic.campaign.executioner.condition', Mautic\CampaignBundle\Executioner\Event\ConditionExecutioner::class);
    $services->set('mautic.campaign.executioner.decision', Mautic\CampaignBundle\Executioner\Event\DecisionExecutioner::class);
    $services->set('mautic.campaign.event_executioner', Mautic\CampaignBundle\Executioner\EventExecutioner::class);
    $services->set('mautic.campaign.helper.decision', Mautic\CampaignBundle\Executioner\Helper\DecisionHelper::class);
    $services->set('mautic.campaign.helper.inactivity', Mautic\CampaignBundle\Executioner\Helper\InactiveHelper::class);
    $services->set('mautic.campaign.helper.removed_contact_tracker', Mautic\CampaignBundle\Helper\RemovedContactTracker::class);
    $services->set('mautic.campaign.helper.notification', Mautic\CampaignBundle\Executioner\Helper\NotificationHelper::class);
    $services->set('mautic.campaign.legacy_event_dispatcher', Mautic\CampaignBundle\Executioner\Dispatcher\LegacyEventDispatcher::class);
    $services->set('mautic.campaign.membership.adder', Mautic\CampaignBundle\Membership\Action\Adder::class);
    $services->set('mautic.campaign.membership.remover', Mautic\CampaignBundle\Membership\Action\Remover::class);
    $services->set('mautic.campaign.membership.event_dispatcher', Mautic\CampaignBundle\Membership\EventDispatcher::class);
    $services->set('mautic.campaign.membership.manager', Mautic\CampaignBundle\Membership\MembershipManager::class);
    $services->set('mautic.campaign.membership.builder', Mautic\CampaignBundle\Membership\MembershipBuilder::class);
    $services->alias('mautic.campaign.model.campaign', Mautic\CampaignBundle\Model\CampaignModel::class);
    $services->alias('mautic.campaign.model.event', Mautic\CampaignBundle\Model\EventModel::class);
    $services->alias('mautic.campaign.model.event_log', Mautic\CampaignBundle\Model\EventLogModel::class);
    $services->alias('mautic.campaign.model.summary', Mautic\CampaignBundle\Model\SummaryModel::class);
    $services->alias('mautic.campaign.repository.campaign', Mautic\CampaignBundle\Entity\CampaignRepository::class);
    $services->alias('mautic.campaign.repository.lead', Mautic\CampaignBundle\Entity\LeadRepository::class);
    $services->alias('mautic.campaign.repository.event', Mautic\CampaignBundle\Entity\EventRepository::class);
    $services->alias('mautic.campaign.repository.lead_event_log', Mautic\CampaignBundle\Entity\LeadEventLogRepository::class);
    $services->alias('mautic.campaign.repository.summary', Mautic\CampaignBundle\Entity\SummaryRepository::class);
    $services->alias('mautic.campaign.executioner.inactive', Mautic\CampaignBundle\Executioner\InactiveExecutioner::class);
    $services->alias('mautic.campaign.executioner.scheduled', Mautic\CampaignBundle\Executioner\ScheduledExecutioner::class);
    $services->alias('mautic.campaign.scheduler.optimized', Mautic\CampaignBundle\Executioner\Scheduler\Mode\Optimized::class);
    $services->alias('mautic.campaign.event_logger', Mautic\CampaignBundle\Executioner\Logger\EventLogger::class);
    $services->alias('mautic.campaign.executioner.kickoff', Mautic\CampaignBundle\Executioner\KickoffExecutioner::class);
    $services->alias('mautic.campaign.scheduler', Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler::class);
    $services->alias('mautic.campaign.executioner.action', Mautic\CampaignBundle\Executioner\Event\ActionExecutioner::class);
    $services->alias('mautic.campaign.executioner.realtime', Mautic\CampaignBundle\Executioner\RealTimeExecutioner::class);
    $services->alias('mautic.campaign.event_collector', Mautic\CampaignBundle\EventCollector\EventCollector::class)
        ->deprecate('mautic/mautic', '7.2', 'The "%alias_id%" service alias is deprecated. Use the "'.Mautic\CampaignBundle\EventCollector\EventCollector::class.'" service instead.');
    $services->alias('mautic.campaign.fixture.campaign', Mautic\CampaignBundle\DataFixtures\ORM\CampaignData::class)
        ->deprecate('mautic/mautic', '7.2', 'The "%alias_id%" service alias is deprecated. Use the "'.Mautic\CampaignBundle\DataFixtures\ORM\CampaignData::class.'" service instead.');
    $services->set(Mautic\CampaignBundle\Executioner\ScheduledExecutioner::class)->tag('kernel.reset', ['method' => 'reset']);

    if ('test' === ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'prod')) {
        $services->set(Mautic\CampaignBundle\Executioner\TestInactiveExecutioner::class)
            ->decorate(Mautic\CampaignBundle\Executioner\InactiveExecutioner::class)
            ->tag('kernel.reset', ['method' => 'reset']);

        $services->set(Mautic\CampaignBundle\Executioner\TestScheduledExecutioner::class)
            ->decorate(Mautic\CampaignBundle\Executioner\ScheduledExecutioner::class)
            ->tag('kernel.reset', ['method' => 'reset']);
    }
};
