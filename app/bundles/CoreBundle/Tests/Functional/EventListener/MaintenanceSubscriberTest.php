<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Entity\AuditLog;
use Mautic\CoreBundle\Entity\Notification;
use Mautic\CoreBundle\Event\MaintenanceEvent;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MaintenanceSubscriberTest extends MauticMysqlTestCase
{
    private const ADMIN_USER = 'admin';

    public function testMaintenanceDataCleanUp(): void
    {
        $admin = $this->getUser(self::ADMIN_USER);
        $this->assertInstanceOf(User::class, $admin);

        $threeDaysAgo  = new \DateTime('3 days ago', new \DateTimeZone('UTC'));
        $today         = new \DateTime('+1 min', new \DateTimeZone('UTC'));

        $this->createTestAuditLogs($admin, $threeDaysAgo, $today);
        $this->createTestNotifications($admin, $threeDaysAgo, $today);

        /** @var TranslatorInterface $translator */
        $translator = self::getContainer()->get(TranslatorInterface::class);

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);

        $event = $dispatcher->dispatch(new MaintenanceEvent(2, false, 0), CoreEvents::MAINTENANCE_CLEANUP_DATA);
        $stats = $event->getStats();

        $this->assertArrayHasKey($translator->trans('mautic.maintenance.audit_log'), $stats);
        $this->assertSame(6, $stats[$translator->trans('mautic.maintenance.audit_log')]);
        $this->assertArrayHasKey($translator->trans('mautic.maintenance.notifications'), $stats);
        $this->assertSame(4, $stats[$translator->trans('mautic.maintenance.notifications')]);
    }

    private function createTestAuditLogs(User $admin, \DateTime $threeDaysAgo, \DateTime $today): void
    {
        $logs = [
            // Admin entries (user_id = admin->getId())
            ['user' => $admin, 'userName' => 'Admin User', 'bundle' => 'campaign', 'object' => 'campaign', 'objectId' => 8, 'action' => 'update', 'dateAdded' => $threeDaysAgo],
            ['user' => $admin, 'userName' => 'Admin User', 'bundle' => 'campaign', 'object' => 'campaign', 'objectId' => 8, 'action' => 'update', 'dateAdded' => $threeDaysAgo],
            ['user' => $admin, 'userName' => 'Admin User', 'bundle' => 'campaign', 'object' => 'campaign', 'objectId' => 8, 'action' => 'create', 'dateAdded' => $today],
            ['user' => $admin, 'userName' => 'Admin User', 'bundle' => 'asset',    'object' => 'asset',    'objectId' => 1,  'action' => 'update', 'dateAdded' => $threeDaysAgo],
            ['user' => $admin, 'userName' => 'Admin User', 'bundle' => 'page',     'object' => 'page',     'objectId' => 2,  'action' => 'create', 'dateAdded' => $today],
            ['user' => $admin, 'userName' => 'Admin User', 'bundle' => 'lead',     'object' => 'company',  'objectId' => 5,  'action' => 'update', 'dateAdded' => $today],
            ['user' => $admin, 'userName' => 'Admin User', 'bundle' => 'lead',     'object' => 'company',  'objectId' => 5,  'action' => 'update', 'dateAdded' => $threeDaysAgo],

            // System entries (user_id = 0)
            ['user' => null,   'userName' => 'System',     'bundle' => 'lead',     'object' => 'lead',     'objectId' => 46, 'action' => 'create', 'dateAdded' => $threeDaysAgo],
            ['user' => null,   'userName' => 'System',     'bundle' => 'lead',     'object' => 'lead',     'objectId' => 46, 'action' => 'ipadded', 'dateAdded' => $today],
            ['user' => null,   'userName' => 'System',     'bundle' => 'lead',     'object' => 'lead',     'objectId' => 45, 'action' => 'create', 'dateAdded' => $today],
            ['user' => null,   'userName' => 'System',     'bundle' => 'lead',     'object' => 'lead',     'objectId' => 45, 'action' => 'ipadded', 'dateAdded' => $threeDaysAgo],
        ];

        foreach ($logs as $data) {
            $log = new AuditLog();
            $log->setUserName($data['userName']);
            $log->setBundle($data['bundle']);
            $log->setObject($data['object']);
            $log->setObjectId($data['objectId']);
            $log->setAction($data['action']);
            $log->setDetails([]);
            $log->setDateAdded($data['dateAdded']);
            $log->setIpAddress('127.0.0.1');

            if (null !== $data['user']) {
                $log->setUserId($data['user']->getId());
            } else {
                $log->setUserId(0); // Explicit for system entries
            }

            $this->em->persist($log);
        }

        $this->em->flush();
    }

    private function createTestNotifications(User $admin, \DateTime $threeDaysAgo, \DateTime $today): void
    {
        $notifications = [
            ['type' => 'notice', 'header' => null, 'message' => 'Some data',                  'dateAdded' => $threeDaysAgo, 'iconClass' => 'fa-info-circle'],
            ['type' => 'info',   'header' => null, 'message' => 'View details',               'dateAdded' => $today, 'iconClass' => 'fa-download'],
            ['type' => 'notice', 'header' => null, 'message' => 'Membership has been rebuilt.', 'dateAdded' => $threeDaysAgo, 'iconClass' => 'fa-info-circle'],
            ['type' => 'notice', 'header' => null, 'message' => 'Membership has been rebuilt.', 'dateAdded' => $today, 'iconClass' => 'fa-info-circle'],
            ['type' => 'notice', 'header' => null, 'message' => 'Membership has been rebuilt.', 'dateAdded' => $threeDaysAgo, 'iconClass' => 'fa-info-circle'],
            ['type' => 'notice', 'header' => null, 'message' => 'Membership has been rebuilt.', 'dateAdded' => $today, 'iconClass' => 'fa-info-circle'],
            ['type' => 'notice', 'header' => null, 'message' => 'Membership has been rebuilt.', 'dateAdded' => $threeDaysAgo, 'iconClass' => 'fa-info-circle'],
        ];

        foreach ($notifications as $data) {
            $notification = new Notification();
            $notification->setUser($admin);
            $notification->setType($data['type']);
            $notification->setHeader($data['header']);
            $notification->setMessage($data['message']);
            $notification->setDateAdded($data['dateAdded']);
            $notification->setIconClass($data['iconClass']);
            $notification->setIsRead(false);

            $this->em->persist($notification);
        }

        $this->em->flush();
    }
}
