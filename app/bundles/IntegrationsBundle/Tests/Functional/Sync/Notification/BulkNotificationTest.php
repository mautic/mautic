<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Functional\Sync\Notification;

use Mautic\CoreBundle\Entity\Notification;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\IntegrationsBundle\Sync\Notification\BulkNotification;
use PHPUnit\Framework\Assert;

class BulkNotificationTest extends MauticMysqlTestCase
{
    /**
     * @var BulkNotification
     */
    private $bulkNotification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bulkNotification = static::getContainer()->get('mautic.integrations.sync.notification.bulk_notification');
    }

    public function testNotifications(): void
    {
        $notificationRepository = $this->em->getRepository(Notification::class);

        $this->bulkNotification->addNotification('dup1', 'message 1', 'Integration name', 'Lead', 'lead', 0, 'link 1');
        $this->bulkNotification->addNotification('dup2', 'message 2', 'Integration name', 'Lead', 'lead', 0, 'link 2');
        $this->bulkNotification->addNotification('dup1', 'message 3', 'Integration name', 'Lead', 'lead', 0, 'link 3');

        Assert::assertCount(0, $notificationRepository->findAll());

        $this->bulkNotification->flush();

        /** @var Notification[] $notifications */
        $notifications = $notificationRepository->findAll();
        Assert::assertCount(2, $notifications);
        $this->assertNotification($notifications[0], 'message 1', 'link 1');
        $this->assertNotification($notifications[1], 'message 2', 'link 2');
    }

    private function assertNotification(Notification $notification, string $message, string $link): void
    {
        Assert::assertSame(sprintf('<a href="/s/contacts/view">%s</a> failed to sync with message, &quot;%s&quot;', $link, $message), $notification->getMessage());
    }
}
