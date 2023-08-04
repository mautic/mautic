<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Entity\Notification;
use Mautic\CoreBundle\Entity\NotificationRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Assert;

class NotificationRepositoryTest extends MauticMysqlTestCase
{
    public function testIsDuplicate(): void
    {
        $this->createNotification(2, 'dup1', new \DateTime('-1 day +5 seconds'));
        $this->createNotification(1, 'dup2', new \DateTime('-1 day +5 seconds'));
        $this->em->flush();

        $this->assertDuplicate(true, 2, 'dup1', new \DateTime('-1 day'));
        $this->assertDuplicate(true, 2, 'dup1', new \DateTime('-25 hour'));
        $this->assertDuplicate(false, 2, 'dup1', new \DateTime('-12 hour'));
        $this->assertDuplicate(true, 1, 'dup2', new \DateTime('-1 day'));
        $this->assertDuplicate(false, 1, 'dup1', new \DateTime('-1 day'));
    }

    private function assertDuplicate(bool $expectedIsDuplicate, int $userId, string $deduplicate, \DateTime $from): void
    {
        /** @var NotificationRepository $notificationRepository */
        $notificationRepository = $this->em->getRepository(Notification::class);
        $isDuplicate            = $notificationRepository->isDuplicate($userId, md5($deduplicate), $from);

        Assert::assertSame($expectedIsDuplicate, $isDuplicate);
    }

    private function createNotification(int $userId, string $deduplicate, \DateTime $datetime): Notification
    {
        /** @var User $user */
        $user         = $this->em->getReference(User::class, $userId);
        $notification = new Notification();
        $notification->setType('notice');
        $notification->setMessage('Some message');
        $notification->setUser($user);
        $notification->setDateAdded($datetime);
        $notification->setDeduplicate(md5($deduplicate));
        $this->em->persist($notification);

        return $notification;
    }
}
