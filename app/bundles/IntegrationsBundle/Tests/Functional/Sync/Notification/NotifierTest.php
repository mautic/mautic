<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Tests\Functional\Sync\Notification;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\InstallBundle\InstallFixtures\ORM\LeadFieldData;
use Mautic\InstallBundle\InstallFixtures\ORM\RoleData;
use Mautic\IntegrationsBundle\Helper\SyncIntegrationsHelper;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\NotificationDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\Notification\Notifier;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\IntegrationsBundle\Tests\Functional\Services\SyncService\TestExamples\Integration\ExampleIntegration;
use Mautic\IntegrationsBundle\Tests\Functional\Services\SyncService\TestExamples\Sync\SyncDataExchange\ExampleSyncDataExchange;
use Mautic\LeadBundle\DataFixtures\ORM\LoadLeadData;
use Mautic\UserBundle\DataFixtures\ORM\LoadRoleData;
use Mautic\UserBundle\DataFixtures\ORM\LoadUserData;

class NotifierTest extends MauticMysqlTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Populate contacts
        $this->installDatabaseFixtures([LeadFieldData::class, LoadLeadData::class, RoleData::class, LoadRoleData::class, LoadUserData::class]);
    }

    public function testNotifications(): void
    {
        /** @var Connection $connection */
        $connection = $this->container->get('doctrine.dbal.default_connection');

        /** @var SyncIntegrationsHelper $syncIntegrationsHelper */
        $syncIntegrationsHelper = $this->container->get('mautic.integrations.helper.sync_integrations');
        $syncIntegrationsHelper->addIntegration(new ExampleIntegration(new ExampleSyncDataExchange()));

        /** @var Notifier $notifier */
        $notifier = $this->container->get('mautic.integrations.sync.notifier');

        $contactNotification = new NotificationDAO(
            new ObjectChangeDAO(
                ExampleIntegration::NAME,
                'Foo',
                1,
                Contact::NAME,
                1
            ),
            'This is the message'
        );
        $companyNotification = new NotificationDAO(
            new ObjectChangeDAO(
                ExampleIntegration::NAME,
                'Bar',
                2,
                MauticSyncDataExchange::OBJECT_COMPANY,
                2
            ),
            'This is the message'
        );

        $notifier->noteMauticSyncIssue([$contactNotification, $companyNotification]);
        $notifier->finalizeNotifications();

        // Check audit log
        $qb = $connection->createQueryBuilder();
        $qb->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'audit_log')
            ->where(
                $qb->expr()->eq('bundle', $qb->expr()->literal(ExampleIntegration::NAME))
            );

        $this->assertCount(2, $qb->execute()->fetchAll());

        // Contact event log
        $qb = $connection->createQueryBuilder();
        $qb->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'lead_event_log')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('bundle', $qb->expr()->literal('integrations')),
                    $qb->expr()->eq('object', $qb->expr()->literal(ExampleIntegration::NAME))
                )
            );
        $this->assertCount(1, $qb->execute()->fetchAll());

        // User notifications
        $qb = $connection->createQueryBuilder();
        $qb->select('*')
            ->from(MAUTIC_TABLE_PREFIX.'notifications')
            ->where(
                $qb->expr()->eq('icon_class', $qb->expr()->literal('fa-refresh'))
            );
        $this->assertCount(2, $qb->execute()->fetchAll());
    }
}
