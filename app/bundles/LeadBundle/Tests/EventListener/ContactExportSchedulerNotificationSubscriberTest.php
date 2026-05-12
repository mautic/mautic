<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Entity\ContactExportScheduler;
use Mautic\LeadBundle\Event\ContactExportSchedulerEvent;
use Mautic\LeadBundle\EventListener\ContactExportSchedulerNotificationSubscriber;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactExportSchedulerNotificationSubscriberTest extends TestCase
{
    public function testContactExportScheduledNotifiesRequesterAndOtherPublishedAdmins(): void
    {
        $requestingUser = $this->createUser(10, 'Requester User', 'requester@example.com', true);
        $otherAdmin     = $this->createUser(11, 'Admin User', 'admin@example.com', true, true);
        $sameAdmin      = $this->createUser(10, 'Requester User', 'requester@example.com', true, true);
        $inactiveAdmin  = $this->createUser(12, 'Inactive Admin', 'inactive@example.com', false, true);
        $nonAdmin       = $this->createUser(13, 'Regular User', 'user@example.com', true, false);

        $contactExportScheduler = (new ContactExportScheduler())
            ->setUser($requestingUser)
            ->setScheduledDateTime(new \DateTimeImmutable('2026-05-12 10:30:00 +00:00'))
            ->setData(['fileType' => 'csv']);

        $notificationModel = new class extends NotificationModel {
            /**
             * @var array<int, array<int, mixed>>
             */
            public array $notifications = [];

            public function __construct()
            {
            }

            public function addNotification($message, $type = null, $isRead = false, $header = null, $iconClass = null, ?\DateTime $datetime = null, ?User $user = null, ?string $deduplicateValue = null, ?\DateTime $deduplicateDateTimeFrom = null): void
            {
                $this->notifications[] = func_get_args();
            }
        };

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(
                static function (string $key, array $parameters = []): string {
                    return match ($key) {
                        'mautic.lead.export.being.prepared'     => "Requester notification for {$parameters['%user_email%']}",
                        'mautic.lead.export.admin.notification' => sprintf(
                            '%s (%s) requested a %s contact export at %s.',
                            $parameters['%requesting_user_name%'],
                            $parameters['%requesting_user_email%'],
                            $parameters['%file_type%'],
                            $parameters['%requested_at%']
                        ),
                        default => $key,
                    };
                }
            );

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('getAllAdminUsers')
            ->willReturn([$sameAdmin, $otherAdmin, $inactiveAdmin, $nonAdmin]);

        $subscriber = new ContactExportSchedulerNotificationSubscriber($notificationModel, $translator, $userRepository);
        $subscriber->onContactExportScheduled(new ContactExportSchedulerEvent($contactExportScheduler));

        Assert::assertCount(2, $notificationModel->notifications);

        Assert::assertSame('Requester notification for requester@example.com', $notificationModel->notifications[0][0]);
        Assert::assertSame('info', $notificationModel->notifications[0][1]);
        Assert::assertFalse($notificationModel->notifications[0][2]);
        Assert::assertSame('mautic.lead.export.being.prepared.header', $notificationModel->notifications[0][3]);
        Assert::assertSame($requestingUser, $notificationModel->notifications[0][6]);

        Assert::assertSame($otherAdmin, $notificationModel->notifications[1][6]);
        Assert::assertSame('mautic.lead.export.admin.notification.header', $notificationModel->notifications[1][3]);
        Assert::assertSame('info', $notificationModel->notifications[1][1]);
        Assert::assertFalse($notificationModel->notifications[1][2]);
        Assert::assertInstanceOf(\DateTime::class, $notificationModel->notifications[1][5]);
        Assert::assertSame(
            $contactExportScheduler->getScheduledDateTime()->format('Y-m-d H:i:s P'),
            $notificationModel->notifications[1][5]->format('Y-m-d H:i:s P')
        );
        Assert::assertStringContainsString('Requester User', $notificationModel->notifications[1][0]);
        Assert::assertStringContainsString('requester@example.com', $notificationModel->notifications[1][0]);
        Assert::assertStringContainsString('CSV', $notificationModel->notifications[1][0]);
        Assert::assertStringContainsString('2026-05-12 10:30:00 +00:00', $notificationModel->notifications[1][0]);
        Assert::assertStringNotContainsString('http', $notificationModel->notifications[1][0]);
    }

    private function createUser(int $id, string $name, string $email, bool $isPublished, bool $isAdmin = false): User
    {
        $role = new Role();
        $role->setName($isAdmin ? 'Admin' : 'User');
        $role->setIsAdmin($isAdmin);

        $user = new User();
        $user->setFirstName(explode(' ', $name)[0]);
        $user->setLastName(explode(' ', $name)[1] ?? 'User');
        $user->setEmail($email);
        $user->setUsername($email);
        $user->setRole($role);
        $user->setIsPublished($isPublished);

        $reflectionProperty = new \ReflectionProperty(User::class, 'id');
        $reflectionProperty->setValue($user, $id);

        return $user;
    }
}
