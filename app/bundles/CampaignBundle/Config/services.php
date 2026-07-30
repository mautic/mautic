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

    $services->set(Mautic\CampaignBundle\Executioner\ContactFinder\KickoffContactFinder::class);
    $services->set(Mautic\CampaignBundle\Executioner\ContactFinder\ScheduledContactFinder::class);
    $services->set(Mautic\CampaignBundle\Executioner\ContactFinder\InactiveContactFinder::class);
    $services->set(Mautic\CampaignBundle\Executioner\Dispatcher\ActionDispatcher::class);
    $services->set(Mautic\CampaignBundle\Executioner\Dispatcher\ConditionDispatcher::class);
    $services->set(Mautic\CampaignBundle\Executioner\Dispatcher\DecisionDispatcher::class);
    $services->set(Mautic\CampaignBundle\Executioner\Scheduler\Mode\DateTime::class);
    $services->set(Mautic\CampaignBundle\Executioner\Scheduler\Mode\Interval::class);
    $services->set(Mautic\CampaignBundle\Executioner\Event\ConditionExecutioner::class);
    $services->set(Mautic\CampaignBundle\Executioner\Event\DecisionExecutioner::class);
    $services->set('mautic.campaign.event_executioner', Mautic\CampaignBundle\Executioner\EventExecutioner::class);
    $services->set(Mautic\CampaignBundle\Executioner\Helper\DecisionHelper::class);
    $services->set(Mautic\CampaignBundle\Executioner\Helper\InactiveHelper::class);
    $services->set(Mautic\CampaignBundle\Helper\RemovedContactTracker::class);
    $services->set(Mautic\CampaignBundle\Executioner\Helper\NotificationHelper::class);
    $services->set('mautic.campaign.legacy_event_dispatcher', Mautic\CampaignBundle\Executioner\Dispatcher\LegacyEventDispatcher::class);
    $services->set(Mautic\CampaignBundle\Membership\Action\Adder::class);
    $services->set(Mautic\CampaignBundle\Membership\Action\Remover::class);
    $services->set(Mautic\CampaignBundle\Membership\EventDispatcher::class);
    $services->set(Mautic\CampaignBundle\Membership\MembershipManager::class);
    $services->set(Mautic\CampaignBundle\Membership\MembershipBuilder::class);
    $services->alias('mautic.campaign.model.campaign', Mautic\CampaignBundle\Model\CampaignModel::class);
    $services->alias('mautic.campaign.model.event', Mautic\CampaignBundle\Model\EventModel::class);
    $services->alias('mautic.campaign.model.event_log', Mautic\CampaignBundle\Model\EventLogModel::class);
    $services->alias('mautic.campaign.model.summary', Mautic\CampaignBundle\Model\SummaryModel::class);
    $services->alias('mautic.campaign.event_logger', Mautic\CampaignBundle\Executioner\Logger\EventLogger::class);
    $services->alias('mautic.campaign.scheduler', Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler::class);
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
