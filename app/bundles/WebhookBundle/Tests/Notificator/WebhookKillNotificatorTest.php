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
use Symfony\Component\Translation\DataCollectorTranslator;

class WebhookKillNotificatorTest extends \PHPUnit_Framework_TestCase
{
    public function testSend()
    {
        $subject        = 'subject';
        $reason         = 'reason';
        $webhookId      = 1;
        $webhookName    = 'Webhook name';
        $generatedRoute = 'generatedRoute';
        $details        = 'details';
        $createdBy      = 'createdBy';
        $modifiedBy     = null;
        $toUser         = $this->createMock(User::class);
        $toEmail        = 'toEmail';

        $translatorMock = $this->createMock(DataCollectorTranslator::class);
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
                ['objectAction' => 'view', 'objectId' => $webhookId]
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
            ->willReturn($toUser);

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
                $toUser
            );

        $toUser
            ->expects($this->once())
            ->method('getEmail')
            ->willReturn($toEmail);

        $mailHelperMock = $this->createMock(MailHelper::class);
        $mailHelperMock
            ->expects($this->once())
            ->method('setTo')
            ->with($toEmail);
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
