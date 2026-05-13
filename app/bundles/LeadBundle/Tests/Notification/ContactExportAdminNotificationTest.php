<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Notification;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\LeadBundle\Entity\ContactExportScheduler;
use Mautic\LeadBundle\Notification\ContactExportAdminNotification;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactExportAdminNotificationTest extends TestCase
{
    public function testRequestedNotifiesOtherPublishedAdmins(): void
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

        $mailHelper = $this->createMock(MailHelper::class);
        $mailHelper->expects($this->never())
            ->method('getMailer');

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')
            ->with('contact_export_notify_admins')
            ->willReturn(true);

        $dateHelper           = $this->createDateHelper();
        $formattedRequestedAt = $dateHelper->toFull($contactExportScheduler->getScheduledDateTime());

        $notification = new ContactExportAdminNotification($notificationModel, $translator, $userRepository, $mailHelper, $coreParametersHelper, $dateHelper);
        $notification->notifyRequested($contactExportScheduler);

        Assert::assertCount(1, $notificationModel->notifications);
        Assert::assertSame($otherAdmin, $notificationModel->notifications[0][6]);
        Assert::assertSame('mautic.lead.export.admin.notification.header', $notificationModel->notifications[0][3]);
        Assert::assertSame('info', $notificationModel->notifications[0][1]);
        Assert::assertFalse($notificationModel->notifications[0][2]);
        Assert::assertInstanceOf(\DateTime::class, $notificationModel->notifications[0][5]);
        Assert::assertSame(
            $contactExportScheduler->getScheduledDateTime()->format('Y-m-d H:i:s P'),
            $notificationModel->notifications[0][5]->format('Y-m-d H:i:s P')
        );
        Assert::assertStringContainsString('Requester User', $notificationModel->notifications[0][0]);
        Assert::assertStringContainsString('requester@example.com', $notificationModel->notifications[0][0]);
        Assert::assertStringContainsString('CSV', $notificationModel->notifications[0][0]);
        Assert::assertStringContainsString($formattedRequestedAt, $notificationModel->notifications[0][0]);
        Assert::assertStringNotContainsString('http', $notificationModel->notifications[0][0]);
    }

    public function testCompletedSendsSharedAdminEmailWithoutLink(): void
    {
        $requestingUser = $this->createUser(10, 'Requester User', 'requester@example.com', true, true);
        $otherAdmin     = $this->createUser(11, 'Admin User', 'admin@example.com', true, true);
        $thirdAdmin     = $this->createUser(14, 'Audit Admin', 'audit@example.com', true, true);
        $sameAdmin      = $this->createUser(10, 'Requester User', 'requester@example.com', true, true);
        $inactiveAdmin  = $this->createUser(12, 'Inactive Admin', 'inactive@example.com', false, true);
        $nonAdmin       = $this->createUser(13, 'Regular User', 'user@example.com', true, false);

        $contactExportScheduler = (new ContactExportScheduler())
            ->setUser($requestingUser)
            ->setScheduledDateTime(new \DateTimeImmutable('2026-05-12 10:30:00 +00:00'))
            ->setData(['fileType' => 'csv']);

        $notificationModel = $this->createMock(NotificationModel::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')
            ->willReturnCallback(
                static function (string $key, array $parameters = []): string {
                    return match ($key) {
                        'mautic.lead.export.admin.email_subject' => 'Contact export completed',
                        'mautic.lead.export.admin.email'         => sprintf(
                            'Hi, Initiated by: %s <%s> Requested at: %s Completed at: %s Status: %s Export type: %s This notification is for security awareness only. The export download link is not included.',
                            $parameters['%requesting_user_name%'],
                            $parameters['%requesting_user_email%'],
                            $parameters['%requested_at%'],
                            $parameters['%completed_at%'],
                            $parameters['%status%'],
                            $parameters['%file_type%']
                        ),
                        default => $key,
                    };
                }
            );

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->once())
            ->method('getAllAdminUsers')
            ->willReturn([$sameAdmin, $otherAdmin, $thirdAdmin, $inactiveAdmin, $nonAdmin]);

        $mailHelper = $this->createMock(MailHelper::class);
        $mailHelper->expects($this->once())
            ->method('getMailer')
            ->with(true)
            ->willReturnSelf();
        $mailHelper->expects($this->once())
            ->method('setTo')
            ->with(['admin@example.com' => 'Admin User'])
            ->willReturn(true);
        $mailHelper->expects($this->once())
            ->method('setCc')
            ->with(['audit@example.com' => 'Audit Admin'])
            ->willReturn(true);
        $mailHelper->expects($this->once())
            ->method('setSubject')
            ->with('Contact export completed');
        $dateHelper           = $this->createDateHelper();
        $formattedRequestedAt = $dateHelper->toFull($contactExportScheduler->getScheduledDateTime());

        $mailHelper->expects($this->once())
            ->method('setBody')
            ->with($this->callback(function (string $message) use ($formattedRequestedAt): bool {
                Assert::assertStringContainsString('Hi,', $message);
                Assert::assertStringContainsString('Requester User', $message);
                Assert::assertStringContainsString('requester@example.com', $message);
                Assert::assertStringContainsString('Requested at: '.$formattedRequestedAt, $message);
                Assert::assertStringContainsString('Completed at:', $message);
                Assert::assertStringContainsString('Status: Completed', $message);
                Assert::assertStringContainsString('Export type: CSV', $message);
                Assert::assertStringContainsString('download link is not included', $message);
                Assert::assertStringNotContainsString('http', $message);

                return true;
            }));
        $mailHelper->expects($this->once())
            ->method('parsePlainText');
        $mailHelper->expects($this->once())
            ->method('send')
            ->with(true);

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')
            ->with('contact_export_notify_admins')
            ->willReturn(true);

        $notification = new ContactExportAdminNotification($notificationModel, $translator, $userRepository, $mailHelper, $coreParametersHelper, $dateHelper);
        $notification->notifyCompleted($contactExportScheduler);
    }

    public function testDoesNothingWhenDisabled(): void
    {
        $requestingUser = $this->createUser(10, 'Requester User', 'requester@example.com', true, true);

        $contactExportScheduler = (new ContactExportScheduler())
            ->setUser($requestingUser)
            ->setScheduledDateTime(new \DateTimeImmutable('2026-05-12 10:30:00 +00:00'))
            ->setData(['fileType' => 'csv']);

        $notificationModel = $this->createMock(NotificationModel::class);
        $notificationModel->expects($this->never())
            ->method('addNotification');

        $translator = $this->createMock(TranslatorInterface::class);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository->expects($this->never())
            ->method('getAllAdminUsers');

        $mailHelper = $this->createMock(MailHelper::class);
        $mailHelper->expects($this->never())
            ->method('getMailer');

        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')
            ->with('contact_export_notify_admins')
            ->willReturn(false);

        $dateHelper = $this->createDateHelper();

        $notification = new ContactExportAdminNotification($notificationModel, $translator, $userRepository, $mailHelper, $coreParametersHelper, $dateHelper);
        $notification->notifyRequested($contactExportScheduler);
        $notification->notifyCompleted($contactExportScheduler);
    }

    private function createDateHelper(): DateHelper
    {
        return new DateHelper(
            'Y-m-d H:i:s P',
            'Y-m-d',
            'Y-m-d',
            'H:i:s',
            $this->createMock(TranslatorInterface::class),
            $this->createMock(CoreParametersHelper::class)
        );
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
