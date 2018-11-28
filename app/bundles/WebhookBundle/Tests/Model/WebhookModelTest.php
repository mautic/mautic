<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use JMS\Serializer\Serializer;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookQueue;
use Mautic\WebhookBundle\Entity\WebhookQueueRepository;
use Mautic\WebhookBundle\Model\WebhookModel;

class WebhookModelTest extends \PHPUnit_Framework_TestCase
{
    private $parametersHelperMock;
    private $serializerMock;
    private $notificationModelMock;
    private $entityManagerMock;
    private $model;

    protected function setUp()
    {
        $this->parametersHelperMock  = $this->createMock(CoreParametersHelper::class);
        $this->serializerMock        = $this->createMock(Serializer::class);
        $this->notificationModelMock = $this->createMock(NotificationModel::class);
        $this->entityManagerMock     = $this->createMock(EntityManager::class);
        $this->model                 = $this->initModel();
    }

    public function testGetEventsOrderbyDirWhenSetInWebhook()
    {
        $webhook = (new Webhook())->setEventsOrderbyDir('DESC');
        $this->assertEquals('DESC', $this->model->getEventsOrderbyDir($webhook));
    }

    public function testGetEventsOrderbyDirWhenNotSetInWebhook()
    {
        $this->parametersHelperMock->method('getParameter')->willReturn('DESC');
        $this->assertEquals('DESC', $this->initModel()->getEventsOrderbyDir());
    }

    public function testGetEventsOrderbyDirWhenWebhookNotProvided()
    {
        $this->parametersHelperMock->method('getParameter')->willReturn('DESC');
        $this->assertEquals('DESC', $this->initModel()->getEventsOrderbyDir());
    }

    public function testGetWebhookPayloadForPayloadInWebhook()
    {
        $payload = ['the' => 'payload'];
        $webhook = new Webhook();
        $webhook->setPayload($payload);

        $this->assertEquals($payload, $this->model->getWebhookPayload($webhook));
    }

    public function testGetWebhookPayloadForQueueLoadedFromDatabase()
    {
        $queueMock = $this->createMock(WebhookQueue::class);
        $webhook   = new Webhook();
        $event     = new Event();
        $event->setEventType('leads');
        $queueMock->method('getPayload')->willReturn('{"the": "payload"}');
        $queueMock->method('getEvent')->willReturn($event);
        $queueMock->method('getDateAdded')->willReturn(new \DateTime('2018-04-10T15:04:57+00:00'));
        $queueMock->method('getId')->willReturn(12);

        $queueRepositoryMock = $this->createMock(WebhookQueueRepository::class);

        $this->parametersHelperMock->expects($this->at(5))
            ->method('getParameter')
            ->with('queue_mode')
            ->willReturn(WebhookModel::COMMAND_PROCESS);

        $this->entityManagerMock->expects($this->at(0))
            ->method('getRepository')
            ->with('MauticWebhookBundle:WebhookQueue')
            ->willReturn($queueRepositoryMock);

        $this->entityManagerMock->expects($this->once())
            ->method('detach')
            ->with($queueMock);

        $queueRepositoryMock->expects($this->once())
            ->method('getEntities')
            ->willReturn([[$queueMock]]);

        $expectedPayload = [
            'leads' => [
                [
                    'the'       => 'payload',
                    'timestamp' => '2018-04-10T15:04:57+00:00',
                ],
            ],
        ];

        $this->assertEquals($expectedPayload, $this->initModel()->getWebhookPayload($webhook));
    }

    public function testGetWebhookPayloadForQueueInWebhook()
    {
        $queue   = new WebhookQueue();
        $webhook = new Webhook();
        $event   = new Event();
        $event->setEventType('leads');
        $queue->setPayload('{"the": "payload"}');
        $queue->setEvent($event);
        $queue->setDateAdded(new \DateTime('2018-04-10T15:04:57+00:00'));
        $webhook->addQueue($queue);

        $this->parametersHelperMock->expects($this->at(5))
            ->method('getParameter')
            ->with('queue_mode')
            ->willReturn(WebhookModel::IMMEDIATE_PROCESS);

        $expectedPayload = [
            'leads' => [
                [
                    'the'       => 'payload',
                    'timestamp' => '2018-04-10T15:04:57+00:00',
                ],
            ],
        ];

        $this->assertEquals($expectedPayload, $this->initModel()->getWebhookPayload($webhook));
    }

    private function initModel()
    {
        $model = new WebhookModel(
            $this->parametersHelperMock,
            $this->serializerMock,
            $this->notificationModelMock
        );

        $model->setEntityManager($this->entityManagerMock);

        return $model;
    }
}
