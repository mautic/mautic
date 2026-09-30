<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Entity\Notification;
use Mautic\CoreBundle\Entity\NotificationRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;

final class NotificationRepositoryTest extends MauticMysqlTestCase
{
    private const MINUS_DAY_DATE_TIME = '-1 day';

    private int $userId1;

    private int $userId2;

    protected function setUp(): void
    {
        parent::setUp();

        // Fetch the two users created by LoadUserData / essential fixtures
        $users = $this->em->getRepository(User::class)->findBy([], ['id' => 'ASC'], 2);

        if (count($users) < 2) {
            $this->fail('Essential fixtures did not load at least 2 users (admin + sales expected)');
        }

        $this->userId1 = $users[0]->getId();  // admin
        $this->userId2 = $users[1]->getId();  // sales
    }

    public function testIsDuplicate(): void
    {
        $this->createNotification($this->userId2, 'dup1', new \DateTime(self::MINUS_DAY_DATE_TIME.' +5 seconds'));
        $this->createNotification($this->userId1, 'dup2', new \DateTime(self::MINUS_DAY_DATE_TIME.' +5 seconds'));
        $this->em->flush();

        $this->assertDuplicate(true, $this->userId2, 'dup1', new \DateTime(self::MINUS_DAY_DATE_TIME));
        $this->assertDuplicate(true, $this->userId2, 'dup1', new \DateTime('-25 hour'));
        $this->assertDuplicate(false, $this->userId2, 'dup1', new \DateTime('-12 hour'));
        $this->assertDuplicate(true, $this->userId1, 'dup2', new \DateTime(self::MINUS_DAY_DATE_TIME));
        $this->assertDuplicate(false, $this->userId1, 'dup1', new \DateTime(self::MINUS_DAY_DATE_TIME));
    }

    private function assertDuplicate(bool $expectedIsDuplicate, int $userId, string $deduplicate, \DateTime $from): void
    {
        /** @var NotificationRepository $notificationRepository */
        $notificationRepository = $this->em->getRepository(Notification::class);
        $isDuplicate            = $notificationRepository->isDuplicate($userId, md5($deduplicate), $from);

        $this->assertSame($expectedIsDuplicate, $isDuplicate);
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
