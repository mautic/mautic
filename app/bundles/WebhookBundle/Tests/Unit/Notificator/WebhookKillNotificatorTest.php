<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Unit\Notificator;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Event\WebhookNotificationEvent;
use Mautic\WebhookBundle\Notificator\WebhookKillNotificator;
use Mautic\WebhookBundle\Notificator\WebhookNotificationSender;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class WebhookKillNotificatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translatorMock;

    /**
     * @var MockObject&NotificationModel
     */
    private MockObject $notificationModelMock;

    /**
     * @var MockObject&EntityManager
     */
    private MockObject $entityManagerMock;

    /**
     * @var MockObject&MailHelper
     */
    private MockObject $mailHelperMock;

    /**
     * @var MockObject&Webhook
     */
    private MockObject $webhook;

    /**
     * @var MockObject&CoreParametersHelper
     */
    private MockObject $coreParamHelperMock;

    private WebhookKillNotificator $webhookKillNotificator;

    private string $subject = 'subject';

    private string $reason = 'reason';

    private string $webhookName = 'Webhook name';

    private string $generatedRoute = 'generatedRoute';

    private string $details = 'details';

    private string $createdBy = 'createdBy';

    private MockObject&User $owner;

    private string $ownerEmail = 'toEmail';

    private ?string $modifiedBy = null;

    /**
     * @var MockObject|UserRepository
     */
    private $userRepositoryMock;

    private WebhookNotificationSender $webhookNotificationSender;

    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->translatorMock        = $this->createMock(TranslatorInterface::class);
        $this->notificationModelMock = $this->createMock(NotificationModel::class);
        $this->entityManagerMock     = $this->createMock(EntityManager::class);
        $this->mailHelperMock        = $this->createMock(MailHelper::class);
        $this->coreParamHelperMock   = $this->createMock(CoreParametersHelper::class);
        $this->webhook               = $this->createMock(Webhook::class);
        $this->userRepositoryMock    = $this->createMock(UserRepository::class);
        $twig                        = $this->createMock(Environment::class);
        $this->eventDispatcher       = $this->createMock(EventDispatcherInterface::class);

        $webhookNotificationEventMock =  $this->createMock(WebhookNotificationEvent::class);
        $webhookNotificationEventMock->method('canSend')->willReturn(true);

        $twig->expects(self::once())
            ->method('render')
            ->willReturn($this->details);

        $this->eventDispatcher->method('dispatch')
            ->willReturn(
                $webhookNotificationEventMock
            );
        $this->webhookNotificationSender =new WebhookNotificationSender(
            $twig,
            $this->notificationModelMock,
            $this->entityManagerMock,
            $this->mailHelperMock,
            $this->coreParamHelperMock,
            $this->userRepositoryMock,
            $this->eventDispatcher
        );
    }

    public function testSendToOwner(): void
    {
        $this->mockCommonMethods(1);
        $this->webhook
            ->expects($this->once())
            ->method('getCreatedBy')
            ->willReturn($this->createdBy);

        $this->webhook
            ->expects($this->once())
            ->method('getModifiedBy')
            ->willReturn($this->modifiedBy);

        $this->entityManagerMock
            ->expects($this->once())
            ->method('getReference')
            ->with(User::class, $this->createdBy)
            ->willReturn($this->owner);

        $this->notificationModelMock
            ->expects($this->once())
            ->method('addNotification')
            ->with(
                $this->details,
                'error',
                false,
                $this->subject,
                null,
                false,
                $this->owner
            );

        $this->mailHelperMock
            ->expects($this->once())
            ->method('setTo')
            ->with([$this->ownerEmail]);

        $this->webhookKillNotificator->send($this->webhook, $this->reason);
    }

    public function testSendToModifier(): void
    {
        $this->ownerEmail     = 'ownerEmail';
        $this->modifiedBy     = 'modifiedBy';
        $modifier             = $this->createMock(User::class);
        $modifierEmail        = 'modifierEmail';

        $this->mockCommonMethods(1);
        $this->webhook
            ->expects($this->exactly(2))
            ->method('getCreatedBy')
            ->willReturn($this->createdBy);
        $this->webhook
            ->expects($this->exactly(3))
            ->method('getModifiedBy')
            ->willReturn($this->modifiedBy);
        $matcher = $this->exactly(2);

        $this->entityManagerMock->expects($matcher)
            ->method('getReference')->willReturnCallback(function (string $entityClass, string|int $entityId) use ($matcher, $modifier) {
                $this->assertSame(User::class, $entityClass);
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->createdBy, $entityId);

                    return $this->owner;
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->modifiedBy, $entityId);

                    return $modifier;
                }
            });

        $this->notificationModelMock
            ->expects($this->once())
            ->method('addNotification')
            ->with(
                $this->details,
                'error',
                false,
                $this->subject,
                null,
                false,
                $modifier
            );

        $modifier
            ->expects($this->once())
            ->method('getEmail')
            ->willReturn($modifierEmail);

        $this->mailHelperMock
            ->expects($this->once())
            ->method('setTo')
            ->with([$modifierEmail]);
        $this->mailHelperMock
            ->expects($this->once())
            ->method('setCc')
            ->with([$this->ownerEmail], null);

        $this->webhookKillNotificator->send($this->webhook, $this->reason);
    }

    private function mockCommonMethods(int $sentToAuthor, ?string $emailToSend = null): void
    {
        $this->coreParamHelperMock->expects($this->any())
            ->method('get')
            ->willReturnOnConsecutiveCalls('from_name', $sentToAuthor, $emailToSend);

        $this->webhookKillNotificator = new WebhookKillNotificator(
            $this->webhookNotificationSender,
            $this->translatorMock
        );
        $this->owner                  = $this->createMock(User::class);

        $htmlUrl = '<a href="'.$this->generatedRoute.'" data-toggle="ajax">'.$this->webhookName.'</a>';
        $matcher = $this->exactly(2);
        $this->translatorMock->expects($matcher)
            ->method('trans')->willReturnCallback(function (...$parameters) use ($matcher, $htmlUrl) {
                if (1 === $matcher->numberOfInvocations()) {
                    $this->assertSame('mautic.webhook.stopped', $parameters[0]);

                    return $this->subject;
                }
                if (2 === $matcher->numberOfInvocations()) {
                    $this->assertSame($this->reason, $parameters[0]);

                    return $this->reason;
                }
                if (3 === $matcher->numberOfInvocations()) {
                    $this->assertSame('mautic.webhook.stopped.details', $parameters[0]);
                    $this->assertSame(['%reason%'  => $this->reason, '%webhook%' => $htmlUrl], $parameters[1]);

                    return $this->details;
                }
            });

        $this->webhook->expects($this->once())
            ->method('getUnHealthySince')
            ->willReturn(new \DateTimeImmutable());

        if ($sentToAuthor) {
            $this->owner
                ->expects($this->once())
                ->method('getEmail')
                ->willReturn($this->ownerEmail);
        }

        $this->mailHelperMock
            ->expects($this->once())
            ->method('setSubject')
            ->with($this->subject);
        $this->mailHelperMock
            ->expects($this->once())
            ->method('setBody')
            ->with($this->details);
    }

    public function testSendToAuthorWithCC(): void
    {
        $subject         = 'subject';
        $reason          = 'reason';
        $webhookName     = 'Webhook name';
        $generatedRoute  = 'generatedRoute';
        $details         = 'details';
        $createdById     = 1;
        $owner           = $this->createMock(User::class);
        $ownerEmail      = 'owner-email@email.com';
        $modifiedById    = 2;
        $modifiedBy      = $this->createMock(User::class);
        $modifiedByEmail = 'modified-by@email.com';
        $htmlUrl         = '<a href="'.$generatedRoute.'" data-toggle="ajax">'.$webhookName.'</a>';

        $this->translatorMock
            ->method('trans')
            ->willReturnMap([
                ['mautic.webhook.stopped', [], null, null, $subject],
                [$reason, [], null, null, $reason],
                [
                    'mautic.webhook.stopped.details',
                    [
                        '%reason%'  => $reason,
                        '%webhook%' => $htmlUrl,
                    ],
                    null,
                    null,
                    $details,
                ],
            ]);

        $this->webhook->expects($this->once())
            ->method('getUnHealthySince')
            ->willReturn(new \DateTimeImmutable());

        $this->webhook
            ->expects($this->exactly(2))
            ->method('getCreatedBy')
            ->willReturn($createdById);
        $this->webhook
            ->expects($this->exactly(3))
            ->method('getModifiedBy')
            ->willReturn($modifiedById);

        $this->entityManagerMock
            ->method('getReference')
            ->willReturnMap([
                [User::class, $createdById, $owner],
                [User::class, $modifiedById, $modifiedBy],
            ]);

        $this->notificationModelMock
            ->expects($this->once())
            ->method('addNotification')
            ->with(
                $details,
                'error',
                false,
                $subject,
                null,
                null,
                $modifiedBy
            );

        $modifiedBy->expects(self::atLeastOnce())->method('getEmail')->willReturn($modifiedByEmail);
        $owner->expects(self::atLeastOnce())->method('getEmail')->willReturn($ownerEmail);

        $this->mailHelperMock
            ->expects($this->once())
            ->method('setTo')
            ->with([$modifiedByEmail], null);
        $this->mailHelperMock
            ->expects($this->once())
            ->method('setCc')
            ->with([$ownerEmail], null);
        $this->mailHelperMock
            ->expects($this->once())
            ->method('setSubject')
            ->with($subject);
        $this->mailHelperMock
            ->expects($this->once())
            ->method('setBody')
            ->with($details);

        $this->coreParamHelperMock->expects(self::atLeastOnce())
            ->method('get')
            ->willReturnMap([
                ['webhook_send_notification_to_author', 1, true],
                ['mailer_from_name', null, 'from_name'],
            ]);

        $webhookKillNotificator = new WebhookKillNotificator(
            $this->webhookNotificationSender,
            $this->translatorMock
        );
        $webhookKillNotificator->send($this->webhook, $reason);
    }

    public function testSendToWebHookNotificationEmail(): void
    {
        $subject        = 'subject';
        $reason         = 'reason';
        $webhookName    = 'Webhook name';
        $generatedRoute = 'generatedRoute';
        $details        = 'details';
        $createdById    = 1;
        $owner          = $this->createMock(User::class);
        $ownerEmail     = 'owner@email.com';
        $modifiedBy     = null;
        $htmlUrl        = '<a href="'.$generatedRoute.'" data-toggle="ajax">'.$webhookName.'</a>';

        $this->translatorMock
            ->method('trans')
            ->willReturnMap([
                ['mautic.webhook.stopped', [], null, null, $subject],
                [$reason, [], null, null, $reason],
                [
                    'mautic.webhook.stopped.details',
                    [
                        '%reason%'  => $reason,
                        '%webhook%' => $htmlUrl,
                    ],
                    null,
                    null,
                    $details,
                ],
            ]);

        $this->webhook->expects($this->once())
            ->method('getUnHealthySince')
            ->willReturn(new \DateTimeImmutable());

        $this->webhook
            ->expects($this->once())
            ->method('getCreatedBy')
            ->willReturn($createdById);
        $this->webhook
            ->expects($this->once())
            ->method('getModifiedBy')
            ->willReturn($modifiedBy);

        $this->entityManagerMock
            ->expects($this->once())
            ->method('getReference')
            ->with(User::class, $createdById)
            ->willReturn($owner);

        $this->notificationModelMock
            ->expects($this->once())
            ->method('addNotification')
            ->with(
                $details,
                'error',
                false,
                $subject,
                null,
                null,
                $owner
            );

        $this->mailHelperMock
            ->expects($this->once())
            ->method('setTo')
            ->with([$ownerEmail], null);
        $this->mailHelperMock
            ->expects($this->once())
            ->method('setSubject')
            ->with($subject);
        $this->mailHelperMock
            ->expects($this->once())
            ->method('setBody')
            ->with($details);

        $this->coreParamHelperMock->expects(self::atLeastOnce())
            ->method('get')
            ->willReturnMap([
                ['webhook_send_notification_to_author', 1, false],
                ['webhook_notification_email_addresses', null, $ownerEmail],
                ['mailer_from_name', null, 'from_name'],
            ]);

        $webhookKillNotificator = new WebhookKillNotificator(
            $this->webhookNotificationSender,
            $this->translatorMock
        );
        $webhookKillNotificator->send($this->webhook, $reason);
    }
}
