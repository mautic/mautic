<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticIntegrationsBundle\Tests\Services\SyncService;


use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MauticIntegrationsBundle\Event\SyncEvent;
use MauticPlugin\MauticIntegrationsBundle\IntegrationEvents;
use MauticPlugin\MauticIntegrationsBundle\Services\SyncService\SyncService;
use MauticPlugin\MauticIntegrationsBundle\Tests\Services\SyncService\TestExamples\EventListener\IntegrationSubscriber;
use MauticPlugin\MauticIntegrationsBundle\Tests\Services\SyncService\TestExamples\Facade\SyncDataExchange\ExampleSyncDataExchange;
use MauticPlugin\MauticIntegrationsBundle\Tests\Services\SyncService\TestExamples\Integration\ExampleIntegration;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SyncServiceTest extends MauticMysqlTestCase
{
    public function testSync()
    {
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->container->get('event_dispatcher');

        $dataExchange = new ExampleSyncDataExchange();
        $dispatcher->addSubscriber(new IntegrationSubscriber($dataExchange));

        $event = new SyncEvent(ExampleIntegration::NAME, new \DateTimeImmutable());
        $dispatcher->dispatch(IntegrationEvents::ON_SYNC_TRIGGERED, $event);

        /** @var SyncService $syncService */
        $syncService = $this->container->get('mautic.integrations.sync.service');

        $syncService->processIntegrationSync($event->getDataExchange(), $event->getMappingManual(), $event->getStartDate());
    }
}