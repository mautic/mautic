<?php

/*
* @copyright   2019 Mautic, Inc. All rights reserved
* @author      Mautic, Inc.
*
* @link        https://mautic.com
*
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace Mautic\WebhookBundle\Tests\Notificator;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\UserBundle\Entity\User;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Notificator\WebhookKillNotificator;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class WebhookKillNotificatorTest extends \PHPUnit\Framework\TestCase
{
    public function testSendToOwner()
    {
        $subject        = 'subject';
        $reason         = 'reason';
        $webhookId      = 1;
        $webhookName    = 'Webhook name';
        $generatedRoute = 'generatedRoute';
        $details        = 'details';
        $createdBy      = 'createdBy';
        $owner          = $this->createMock(User::class);
        $ownerEmail     = 'toEmail';
        $modifiedBy     = null;

        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock
            ->expects($this->at(0))
            ->method('trans')
            ->with('mautic.webhook.stopped')
            ->willReturn($subject);
        $translatorMock
            ->expects($this->at(1))
            ->method('trans')
            ->with($reason)
            ->willReturn($reason);

        $webhook = $this->createMock(Webhook::class);
        $webhook->expects($this->once())
            ->method('getId')
            ->willReturn($webhookId);
        $webhook->expects($this->once())
            ->method('getName')
            ->willReturn($webhookName);

        $routerMock = $this->createMock(Router::class);
        $routerMock
            ->expects($this->once())
            ->method('generate')
            ->with(
                'mautic_webhook_action',
                ['objectAction' => 'view', 'objectId' => $webhookId],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn($generatedRoute);

        $htmlUrl = '<a href="'.$generatedRoute.'" data-toggle="ajax">'.$webhookName.'</a>';

        $translatorMock
            ->expects($this->at(2))
            ->method('trans')
            ->with(
                'mautic.webhook.stopped.details',
                ['%reason%'  => $reason, '%webhook%' => $htmlUrl]
            )
            ->willReturn($details);

        $webhook
            ->expects($this->once())
            ->method('getCreatedBy')
            ->willReturn($createdBy);
        $webhook
            ->expects($this->once())
            ->method('getModifiedBy')
            ->willReturn($modifiedBy);

        $entityManagerMock = $this->createMock(EntityManager::class);
        $entityManagerMock
            ->expects($this->once())
            ->method('getReference')
            ->with('MauticUserBundle:User', $createdBy)
            ->willReturn($owner);

        $notificationModelMock = $this->createMock(NotificationModel::class);
        $notificationModelMock
            ->expects($this->once())
            ->method('addNotification')
            ->with(
                $details,
                'error',
                false,
                $subject,
                null,
                false,
                $owner
            );

        $owner
            ->expects($this->once())
            ->method('getEmail')
            ->willReturn($ownerEmail);

        $mailHelperMock = $this->createMock(MailHelper::class);
        $mailHelperMock
            ->expects($this->once())
            ->method('setTo')
            ->with($ownerEmail);
        $mailHelperMock
            ->expects($this->once())
            ->method('setSubject')
            ->with($subject);
        $mailHelperMock
            ->expects($this->once())
            ->method('setBody')
            ->with($details);

        $webhookKillNotificator = new WebhookKillNotificator($translatorMock, $routerMock, $notificationModelMock, $entityManagerMock, $mailHelperMock);
        $webhookKillNotificator->send($webhook, $reason);
    }

    public function testSendToModifier()
    {
        $subject        = 'subject';
        $reason         = 'reason';
        $webhookId      = 1;
        $webhookName    = 'Webhook name';
        $generatedRoute = 'generatedRoute';
        $details        = 'details';
        $createdBy      = 'createdBy';
        $owner          = $this->createMock(User::class);
        $ownerEmail     = 'ownerEmail';
        $modifiedBy     = 'modifiedBy';
        $modifier       = $this->createMock(User::class);
        $modifierEmail  = 'modifierEmail';

        $translatorMock = $this->createMock(TranslatorInterface::class);
        $translatorMock
            ->expects($this->at(0))
            ->method('trans')
            ->with('mautic.webhook.stopped')
            ->willReturn($subject);
        $translatorMock
            ->expects($this->at(1))
            ->method('trans')
            ->with($reason)
            ->willReturn($reason);

        $webhook = $this->createMock(Webhook::class);
        $webhook->expects($this->once())
            ->method('getId')
            ->willReturn($webhookId);
        $webhook->expects($this->once())
            ->method('getName')
            ->willReturn($webhookName);

        $routerMock = $this->createMock(Router::class);
        $routerMock
            ->expects($this->once())
            ->method('generate')
            ->with(
                'mautic_webhook_action',
                ['objectAction' => 'view', 'objectId' => $webhookId],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn($generatedRoute);

        $htmlUrl = '<a href="'.$generatedRoute.'" data-toggle="ajax">'.$webhookName.'</a>';

        $translatorMock
            ->expects($this->at(2))
            ->method('trans')
            ->with(
                'mautic.webhook.stopped.details',
                ['%reason%'  => $reason, '%webhook%' => $htmlUrl]
            )
            ->willReturn($details);

        $webhook
            ->expects($this->exactly(2))
            ->method('getCreatedBy')
            ->willReturn($createdBy);
        $webhook
            ->expects($this->exactly(3))
            ->method('getModifiedBy')
            ->willReturn($modifiedBy);

        $entityManagerMock = $this->createMock(EntityManager::class);
        $entityManagerMock
            ->expects($this->at(0))
            ->method('getReference')
            ->with('MauticUserBundle:User', $createdBy)
            ->willReturn($owner);

        $entityManagerMock
            ->expects($this->at(1))
            ->method('getReference')
            ->with('MauticUserBundle:User', $modifiedBy)
            ->willReturn($modifier);

        $notificationModelMock = $this->createMock(NotificationModel::class);
        $notificationModelMock
            ->expects($this->once())
            ->method('addNotification')
            ->with(
                $details,
                'error',
                false,
                $subject,
                null,
                false,
                $modifier
            );

        $owner
            ->expects($this->once())
            ->method('getEmail')
            ->willReturn($ownerEmail);
        $modifier
            ->expects($this->once())
            ->method('getEmail')
            ->willReturn($modifierEmail);

        $mailHelperMock = $this->createMock(MailHelper::class);
        $mailHelperMock
            ->expects($this->once())
            ->method('setTo')
            ->with($modifierEmail);
        $mailHelperMock
            ->expects($this->once())
            ->method('setCc')
            ->with($ownerEmail);
        $mailHelperMock
            ->expects($this->once())
            ->method('setSubject')
            ->with($subject);
        $mailHelperMock
            ->expects($this->once())
            ->method('setBody')
            ->with($details);

        $webhookKillNotificator = new WebhookKillNotificator($translatorMock, $routerMock, $notificationModelMock, $entityManagerMock, $mailHelperMock);
        $webhookKillNotificator->send($webhook, $reason);
    }
}
