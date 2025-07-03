<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Unit\Notificator;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Notificator\WebhookKillNotificator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WebhookKillNotificatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&TranslatorInterface
     */
    private MockObject $translatorMock;

    /**
     * @var MockObject&Router
     */
    private MockObject $routerMock;

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

    private int $webhookId = 1;

    private string $webhookName = 'Webhook name';

    private string $generatedRoute = 'generatedRoute';

    private string $details = 'details';

    private string $createdBy = 'createdBy';

    private MockObject&User $owner;

    private string $ownerEmail = 'toEmail';

    private ?string $modifiedBy = null;

    protected function setUp(): void
    {
        $this->translatorMock        = $this->createMock(TranslatorInterface::class);
        $this->routerMock            = $this->createMock(Router::class);
        $this->notificationModelMock = $this->createMock(NotificationModel::class);
        $this->entityManagerMock     = $this->createMock(EntityManager::class);
        $this->mailHelperMock        = $this->createMock(MailHelper::class);
        $this->coreParamHelperMock   = $this->createMock(CoreParametersHelper::class);
        $this->webhook               = $this->createMock(Webhook::class);
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
            ->with($this->ownerEmail);

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
            ->with($modifierEmail);
        $this->mailHelperMock
            ->expects($this->once())
            ->method('setCc')
            ->with([$this->ownerEmail => null]);

        $this->webhookKillNotificator->send($this->webhook, $this->reason);
    }

    private function mockCommonMethods(int $sentToAuthor): void
    {
        $this->coreParamHelperMock->expects($this->exactly(1))
            ->method('get')
            ->with('webhook_send_notification_to_author')
            ->willReturn($sentToAuthor);

        $this->webhookKillNotificator = new WebhookKillNotificator($this->translatorMock, $this->routerMock, $this->notificationModelMock, $this->entityManagerMock, $this->mailHelperMock, $this->coreParamHelperMock);
        $this->owner                  = $this->createMock(User::class);

        $htmlUrl = '<a href="'.$this->generatedRoute.'" data-toggle="ajax">'.$this->webhookName.'</a>';
        $matcher = $this->exactly(3);
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
            ->method('getId')
            ->willReturn($this->webhookId);
        $this->webhook->expects($this->once())
            ->method('getName')
            ->willReturn($this->webhookName);

        $this->routerMock
            ->expects($this->once())
            ->method('generate')
            ->with(
                'mautic_webhook_action',
                ['objectAction' => 'view', 'objectId' => $this->webhookId],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn($this->generatedRoute);

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
}
