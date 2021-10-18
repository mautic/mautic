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

class WebhookKillNotificatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|TranslatorInterface
     */
    private $translatorMock;

    /**
     * @var MockObject|Router
     */
    private $routerMock;

    /**
     * @var MockObject|NotificationModel
     */
    private $notificationModelMock;

    /**
     * @var MockObject|EntityManager
     */
    private $entityManagerMock;

    /**
     * @var MockObject|MailHelper
     */
    private $mailHelperMock;

    /**
     * @var MockObject|Webhook
     */
    private $webhook;

    /**
     * @var MockObject|CoreParametersHelper
     */
    private $coreParamHelperMock;

    /**
     * @var WebhookKillNotificator
     */
    private $webhookKillNotificator;

    /**
     * @var string
     */
    private $subject = 'subject';

    /**
     * @var string
     */
    private $reason = 'reason';

    /**
     * @var int
     */
    private $webhookId = 1;

    /**
     * @var string
     */
    private $webhookName = 'Webhook name';

    /**
     * @var string
     */
    private $generatedRoute = 'generatedRoute';

    /**
     * @var string
     */
    private $details = 'details';

    /**
     * @var string
     */
    private $createdBy = 'createdBy';

    /**
     * @var User|null
     */
    private $owner;

    /**
     * @var string
     */
    private $ownerEmail = 'toEmail';

    /**
     * @var string|null
     */
    private $modifiedBy = null;

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
            ->with(\Mautic\UserBundle\Entity\User::class, $this->createdBy)
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
            ->expects($this->at(0))
            ->method('getReference')
            ->withConsecutive(
                [\Mautic\UserBundle\Entity\User::class, $this->createdBy],
                [\Mautic\UserBundle\Entity\User::class, $this->modifiedBy]
            )
            ->willReturnOnConsecutiveCalls($this->owner, $modifier);

        $this->entityManagerMock
            ->expects($this->at(1))
            ->method('getReference')
            ->with('MauticUserBundle:User', $this->modifiedBy)
            ->willReturn($modifier);

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
            ->with($this->ownerEmail);

        $this->webhookKillNotificator->send($this->webhook, $this->reason);
    }

    public function testSendTomailAddresses(): void
    {
        $emailToSend = 'a@test.com, b@test.com';
        $this->mockCommonMethods(0);

        $this->coreParamHelperMock
            ->expects($this->at(1))
            ->method('get')
            ->with('webhook_notification_email_addresses')
            ->willReturn($emailToSend);

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
            ->with('MauticUserBundle:User', $this->createdBy)
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
            ->with(array_map('trim', explode(',', $emailToSend)));

        $this->webhookKillNotificator->send($this->webhook, $this->reason);
    }

    private function mockCommonMethods(int $sentToAuther): void
    {
        $this->coreParamHelperMock
            ->expects($this->at(0))
            ->method('get')
            ->with('webhook_send_notification_to_author')
            ->willReturn($sentToAuther);

        $this->webhookKillNotificator = new WebhookKillNotificator($this->translatorMock, $this->routerMock, $this->notificationModelMock, $this->entityManagerMock, $this->mailHelperMock, $this->coreParamHelperMock);

        $this->owner          = $this->createMock(User::class);
        $this->translatorMock
            ->expects($this->at(0))
            ->method('trans')
            ->with('mautic.webhook.stopped')
            ->willReturn($this->subject);
        $this->translatorMock
            ->expects($this->at(1))
            ->method('trans')
            ->with($this->reason)
            ->willReturn($this->reason);

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

        $htmlUrl = '<a href="'.$this->generatedRoute.'" data-toggle="ajax">'.$this->webhookName.'</a>';

        $this->translatorMock
            ->expects($this->at(2))
            ->method('trans')
            ->with(
                'mautic.webhook.stopped.details',
                ['%reason%'  => $this->reason, '%webhook%' => $htmlUrl]
            )
            ->willReturn($this->details);

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
