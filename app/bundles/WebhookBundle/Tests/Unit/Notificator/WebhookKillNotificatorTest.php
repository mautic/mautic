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

    private User $owner;

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

        $this->entityManagerMock
            ->method('getReference')
            ->withConsecutive([User::class, $this->createdBy], [User::class, $this->modifiedBy])
            ->willReturn($this->owner, $modifier);

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

    private function mockCommonMethods(int $sentToAuther, string $emailToSend = null): void
    {
        $this->coreParamHelperMock
            ->method('get')
            ->withConsecutive(['webhook_send_notification_to_author'], ['webhook_notification_email_addresses'])
            ->willReturnOnConsecutiveCalls($sentToAuther, $emailToSend);

        $this->webhookKillNotificator = new WebhookKillNotificator($this->translatorMock, $this->routerMock, $this->notificationModelMock, $this->entityManagerMock, $this->mailHelperMock, $this->coreParamHelperMock);

        $this->owner          = $this->createMock(User::class);

        $htmlUrl = '<a href="'.$this->generatedRoute.'" data-toggle="ajax">'.$this->webhookName.'</a>';
        $this->translatorMock
            ->method('trans')
            ->withConsecutive(['mautic.webhook.stopped'], [$this->reason], ['mautic.webhook.stopped.details', ['%reason%'  => $this->reason, '%webhook%' => $htmlUrl]])
            ->willReturnOnConsecutiveCalls($this->subject, $this->reason, $this->details);

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

        if ($sentToAuther) {
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
