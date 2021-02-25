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
use JMS\Serializer\SerializerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookQueue;
use Mautic\WebhookBundle\Entity\WebhookQueueRepository;
use Mautic\WebhookBundle\Entity\WebhookRepository;
use Mautic\WebhookBundle\Http\Client;
use Mautic\WebhookBundle\Model\WebhookModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WebhookModelTest extends TestCase
{
    /**
     * @var MockObject|CoreParametersHelper
     */
    private $parametersHelperMock;

    /**
     * @var MockObject|SerializerInterface
     */
    private $serializerMock;

    /**
     * @var MockObject|EntityManager
     */
    private $entityManagerMock;

    /**
     * @var MockObject|WebhookRepository
     */
    private $webhookRepository;

    /**
     * @var MockObject|UserHelper
     */
    private $userHelper;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $eventDispatcherMock;

    /**
     * @var WebhookModel
     */
    private $model;

    /**
     * @var MockObject|Client
     */
    private $httpClientMock;

    protected function setUp(): void
    {
        $this->parametersHelperMock  = $this->createMock(CoreParametersHelper::class);
        $this->serializerMock        = $this->createMock(SerializerInterface::class);
        $this->entityManagerMock     = $this->createMock(EntityManager::class);
        $this->userHelper            = $this->createMock(UserHelper::class);
        $this->webhookRepository     = $this->createMock(WebhookRepository::class);
        $this->httpClientMock        = $this->createMock(Client::class);
        $this->entityManagerMock     = $this->createMock(EntityManager::class);
        $this->eventDispatcherMock   = $this->createMock(EventDispatcher::class);
        $this->model                 = $this->initModel();
    }

    public function testSaveEntity(): void
    {
        $entity = new Webhook();

        // The secret hash is null at first.
        $this->assertNull($entity->getSecret());

        $this->entityManagerMock->expects($this->once())
            ->method('getRepository')
            ->with(Webhook::class)
            ->willReturn($this->webhookRepository);

        $this->webhookRepository->expects($this->once())
            ->method('saveEntity')
            ->with($this->callback(function (Webhook $entity) {
                // The secret hash is not empty on save.
                $this->assertNotEmpty($entity->getSecret());

                return true;
            }));

        $this->model->saveEntity($entity);
    }

    public function testGetEventsOrderbyDirWhenSetInWebhook(): void
    {
        $webhook = (new Webhook())->setEventsOrderbyDir('DESC');
        $this->assertEquals('DESC', $this->model->getEventsOrderbyDir($webhook));
    }

    public function testGetEventsOrderbyDirWhenNotSetInWebhook(): void
    {
        $this->parametersHelperMock->method('get')->willReturn('DESC');
        $this->assertEquals('DESC', $this->initModel()->getEventsOrderbyDir());
    }

    public function testGetEventsOrderbyDirWhenWebhookNotProvided(): void
    {
        $this->parametersHelperMock->method('get')->willReturn('DESC');
        $this->assertEquals('DESC', $this->initModel()->getEventsOrderbyDir());
    }

    public function testGetWebhookPayloadForPayloadInWebhook(): void
    {
        $payload = ['the' => 'payload'];
        $webhook = new Webhook();
        $webhook->setPayload($payload);

        $this->assertEquals($payload, $this->model->getWebhookPayload($webhook));
    }

    public function testGetWebhookPayloadForQueueLoadedFromDatabase(): void
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

        $this->parametersHelperMock->method('get')
            ->willReturnCallback(function ($param) {
                if ('queue_mode' === $param) {
                    return WebhookModel::COMMAND_PROCESS;
                }

                return null;
            });

        $this->entityManagerMock->expects($this->at(0))
            ->method('getRepository')
            ->with(WebhookQueue::class)
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

    public function testGetWebhookPayloadForQueueInWebhook(): void
    {
        $queue   = new WebhookQueue();
        $webhook = new Webhook();
        $event   = new Event();
        $event->setEventType('leads');
        $queue->setPayload('{"the": "payload"}');
        $queue->setEvent($event);
        $queue->setDateAdded(new \DateTime('2018-04-10T15:04:57+00:00'));

        $this->parametersHelperMock->method('get')
            ->willReturnCallback(function ($param) {
                if ('queue_mode' === $param) {
                    return WebhookModel::IMMEDIATE_PROCESS;
                }

                return null;
            });

        $expectedPayload = [
            'leads' => [
                [
                    'the'       => 'payload',
                    'timestamp' => '2018-04-10T15:04:57+00:00',
                ],
            ],
        ];

        $this->assertEquals($expectedPayload, $this->initModel()->getWebhookPayload($webhook, $queue));
    }

    private function initModel(): WebhookModel
    {
        $model = new WebhookModel(
            $this->parametersHelperMock,
            $this->serializerMock,
            $this->httpClientMock,
            $this->eventDispatcherMock
        );

        $model->setEntityManager($this->entityManagerMock);
        $model->setUserHelper($this->userHelper);
        $model->setDispatcher($this->eventDispatcherMock);

        return $model;
    }
}
