<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Services\SyncService;


use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\IntegrationsBundle\Event\SyncEvent;
use MauticPlugin\IntegrationsBundle\IntegrationEvents;
use MauticPlugin\IntegrationsBundle\Sync\SyncService\SyncService;
use MauticPlugin\IntegrationsBundle\Tests\Services\SyncService\TestExamples\EventListener\IntegrationSubscriber;
use MauticPlugin\IntegrationsBundle\Tests\Services\SyncService\TestExamples\Sync\SyncDataExchange\ExampleSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Tests\Services\SyncService\TestExamples\Integration\ExampleIntegration;
use Symfony\Component\EventDispatcher\EventDispatcher;

class SyncServiceTest extends MauticMysqlTestCase
{
    public function setUp()
    {
        parent::setUp();

        // Populate contacts
        $this->installDatabaseFixtures([dirname(__DIR__).'/../../../../app/bundles/LeadBundle/DataFixtures/ORM/LoadLeadData.php']);
    }

    public function testSync()
    {
        // Sleep one second to ensure that the modified date/time stamps of the contacts just created are in the past
        sleep(1);

        // Record now because we're going to sync again
        $now = new \DateTime();

        $prefix = $this->container->getParameter('mautic.db_table_prefix');
        $connection = $this->container->get('doctrine.dbal.default_connection');

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->container->get('event_dispatcher');

        $dataExchange = new ExampleSyncDataExchange();
        $dispatcher->addSubscriber(new IntegrationSubscriber($dataExchange));

        $event = new SyncEvent(ExampleIntegration::NAME, true);
        $dispatcher->dispatch(IntegrationEvents::ON_SYNC_TRIGGERED, $event);

        /** @var SyncService $syncService */
        $syncService = $this->container->get('mautic.integrations.sync.service');

        $syncService->processIntegrationSync($event->getDataExchange(), $event->getMappingManual(), true);
        $payload = $dataExchange->getOrderPayload();

        // Created the 50 known contacts already in Mautic
        $this->assertCount(50, $payload['create']);
        $this->assertCount(0, $payload['update']);

        // Sleep to pass time
        sleep(5);

        // Sync again
        $syncService->processIntegrationSync($event->getDataExchange(), $event->getMappingManual(), true, $now);
        $payload = $dataExchange->getOrderPayload();

        // Now we should have updated the 2 contacts that were already in Mautic
        $this->assertCount(2, $payload['update']);
        $this->assertEquals(
            [
                4 =>
                    [
                        'id'         => 4,
                        'object'     => 'lead',
                        'first_name' => 'Lewis',
                        'last_name'  => 'Syed',
                        'email'      => 'LewisTSyed@gustr.com',
                    ],
                3 =>
                    [
                        'id'         => 3,
                        'object'     => 'lead',
                        'first_name' => 'Nellie',
                        'last_name'  => 'Baird',
                        'email'      => 'NellieABaird@armyspy.com',
                    ],
            ],
            $payload['update']
        );

        // Validate mapping table
        /** @var Connection $connection */

        $qb = $connection->createQueryBuilder();
        $results = $qb->select('count(*) as the_count, m.integration_object_name, m.integration')
            ->from($prefix.'sync_object_mapping', 'm')
            ->groupBy('m.integration, m.integration_object_name')
            ->execute()
            ->fetchAll();

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertEquals(ExampleIntegration::NAME, $result['integration']);
            if (ExampleSyncDataExchange::OBJECT_LEAD === $result['integration_object_name']) {
                // Two leads were created in Mautic from the integration
                $this->assertEquals(2, $result['the_count']);
            } else {
                // Fifty two Mautic contacts were pushed to the integration as the contact object
                $this->assertEquals(52, $result['the_count']);
            }
        }
    }
}