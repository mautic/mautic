<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MaintenanceSubscriberTest extends MauticMysqlTestCase
{
    public function testMaintenanceDataCleanUp(): void
    {
        // Insert the audit_log and notification
        $prefix        = self::getContainer()->getParameter('mautic.db_table_prefix');
        $threeDaysAgo  = (new \DateTime('3 days ago', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $today         = (new \DateTime('+1 min', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        $connection    = $this->em->getConnection();
        $connection->executeQuery("INSERT INTO {$prefix}audit_log (user_id, user_name, bundle, object, object_id, action, details, date_added, ip_address)
            VALUES
                (1, 'Admin User', 'campaign', 'campaign', 8, 'update', 'a:0:{}', '{$threeDaysAgo}', '127.0.0.1'),
                (1, 'Admin User', 'campaign', 'campaign', 8, 'update', 'a:0:{}', '{$threeDaysAgo}', '127.0.0.1'),
                (1, 'Admin User', 'campaign', 'campaign', 8, 'create', 'a:0:{}', '{$today}', '127.0.0.1'),
                (0, 'System', 'lead', 'lead', 46, 'create', 'a:0:{}', '{$threeDaysAgo}', '127.0.0.1'),
                (0, 'System', 'lead', 'lead', 46, 'ipadded', 'a:0:{}', '{$today}', '127.0.0.1'),
                (0, 'System', 'lead', 'lead', 45, 'create', 'a:0:{}', '{$today}', '127.0.0.1'),
                (0, 'System', 'lead', 'lead', 45, 'ipadded', 'a:0:{}', '{$threeDaysAgo}', '127.0.0.1'),
                (1, 'Admin User', 'asset', 'asset', 1, 'update', 'a:0:{}', '{$threeDaysAgo}', '127.0.0.1'),
                (1, 'Admin User', 'page', 'page', 2, 'create', 'a:0:{}', '{$today}', '127.0.0.1'),
                (1, 'Admin User', 'lead', 'company', 5, 'update', 'a:0:{}', '{$today}', '127.0.0.1'),
                (1, 'Admin User', 'lead', 'company', 5, 'update', 'a:0:{}', '{$threeDaysAgo}', '127.0.0.1');"
        );

        $connection->executeQuery("INSERT INTO {$prefix}notifications (user_id, type, header, message, date_added, icon_class, is_read, deduplicate)
            VALUES
              (1, 'notice', NULL, 'Some data', '{$threeDaysAgo}', 'fa-info-circle', 0, NULL),
              (1, 'info', 'NULL', 'View details', '{$today}', 'fa-download', 0, NULL),
              (1, 'notice', NULL, 'Membership has been rebuilt.', '{$threeDaysAgo}', 'fa-info-circle', 0, NULL),
              (1, 'notice', NULL, 'Membership has been rebuilt.', '{$today}', 'fa-info-circle', 0, NULL),
              (1, 'notice', NULL, 'Membership has been rebuilt.', '{$threeDaysAgo}', 'fa-info-circle', 0, NULL),
              (1, 'notice', NULL, 'Membership has been rebuilt.', '{$today}', 'fa-info-circle', 0, NULL),
              (1, 'notice', NULL, 'Membership has been rebuilt.', '{$threeDaysAgo}', 'fa-info-circle', 0, NULL);"
        );

        /** @var TranslatorInterface $translator */
        $translator = self::getContainer()->get('translator');

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');

        $event = $dispatcher->dispatch(new MaintenanceEvent(2, false, 0), CoreEvents::MAINTENANCE_CLEANUP_DATA);
        $stats = $event->getStats();

        $this->assertArrayHasKey($translator->trans('mautic.maintenance.audit_log'), $stats);
        $this->assertSame(6, $stats[$translator->trans('mautic.maintenance.audit_log')]);
        $this->assertArrayHasKey($translator->trans('mautic.maintenance.notifications'), $stats);
        $this->assertSame(4, $stats[$translator->trans('mautic.maintenance.notifications')]);
    }
}
