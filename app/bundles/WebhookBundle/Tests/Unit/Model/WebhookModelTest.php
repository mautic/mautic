<?php

namespace Mautic\WebhookBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\Response;
use JMS\Serializer\SerializerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\Log;
use Mautic\WebhookBundle\Entity\LogRepository;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookQueue;
use Mautic\WebhookBundle\Entity\WebhookQueueRepository;
use Mautic\WebhookBundle\Entity\WebhookRepository;
use Mautic\WebhookBundle\Http\Client;
use Mautic\WebhookBundle\Model\WebhookModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class WebhookModelTest extends TestCase
{
    /**
     * @var MockObject&CoreParametersHelper
     */
    private MockObject $parametersHelperMock;

    /**
     * @var MockObject&SerializerInterface
     */
    private MockObject $serializerMock;

    /**
     * @var MockObject&EntityManager
     */
    private MockObject $entityManagerMock;

    /**
     * @var MockObject&WebhookRepository
     */
    private MockObject $webhookRepository;

    /**
     * @var MockObject&WebhookQueueRepository
     */
    private $webhookQueueRepository;

    /**
     * @var MockObject&UserHelper
     */
    private MockObject $userHelper;

    /**
     * @var MockObject&EventDispatcherInterface
     */
    private MockObject $eventDispatcherMock;

    private WebhookModel $model;

    /**
     * @var MockObject&Client
     */
    private MockObject $httpClientMock;

    protected function setUp(): void
    {
        $this->parametersHelperMock   = $this->createMock(CoreParametersHelper::class);
        $this->serializerMock         = $this->createMock(SerializerInterface::class);
        $this->entityManagerMock      = $this->createMock(EntityManager::class);
        $this->userHelper             = $this->createMock(UserHelper::class);
        $this->webhookRepository      = $this->createMock(WebhookRepository::class);
        $this->webhookQueueRepository = $this->createMock(WebhookQueueRepository::class);
        $this->httpClientMock         = $this->createMock(Client::class);
        $this->eventDispatcherMock    = $this->createMock(EventDispatcher::class);
        $this->model                  = $this->initModel();
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

        $this->entityManagerMock->expects($this->once())
            ->method('getRepository')
            ->with(WebhookQueue::class)
            ->willReturn($queueRepositoryMock);

        $this->entityManagerMock->expects($this->once())
            ->method('detach')
            ->with($queueMock);

        $queueRepositoryMock->expects($this->once())
            ->method('getEntities')
            ->willReturn([$queueMock]);

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

    public function testProcessWebhook(): void
    {
        $webhook = new class extends Webhook {
            public function getId(): int
            {
                return 1;
            }
        };
        $webhook->setWebhookUrl('test-webhook.com');

        $event = new Event();
        $event->setEventType('mautic.email_on_send');

        $queue = new class extends WebhookQueue {
            public function getId(): int
            {
                return 1;
            }
        };
        $queue->setPayload('{"payload": "some data"}');
        $queue->setEvent($event);
        $queue->setDateAdded(new \DateTime('2021-04-01T16:00:00+00:00'));

        $webhookQueueRepoMock = $this->createMock(WebhookQueueRepository::class);
        $webhookLogRepoMock   = $this->createMock(LogRepository::class);
        $webhookRepoMock      = $this->createMock(WebhookRepository::class);

        $this->entityManagerMock->method('getRepository')
            ->willReturnMap([
                [WebhookQueue::class, $webhookQueueRepoMock],
                [Log::class, $webhookLogRepoMock],
                [Webhook::class, $webhookRepoMock],
            ]);

        $webhookQueueRepoMock
            ->method('deleteQueuesById')
            ->with([1]);

        $responsePayload = [
            'mautic.email_on_send' => [
                [
                    'payload'   => 'some data',
                    'timestamp' => '2021-04-01T16:00:00+00:00',
                ],
            ],
        ];
        $this->httpClientMock
            ->method('post')
            ->with('test-webhook.com', $responsePayload)
            ->willReturn(new Response(200, [], 'Success'));

        self::assertTrue($this->model->processWebhook($webhook, $queue));
    }

    public function testMinAndMaxQueueIdWhenNoneIsSet(): void
    {
        $webhook = new class extends Webhook {
            public function getId(): int
            {
                return 1;
            }
        };

        $webhook->setEventsOrderbyDir('ASC');

        $this->entityManagerMock->expects($this->once())
            ->method('getRepository')
            ->with(WebhookQueue::class)
            ->willReturn($this->webhookQueueRepository);

        $this->webhookQueueRepository->method('getTableAlias')->willReturn('w');

        $this->webhookQueueRepository->expects($this->once())
            ->method('getEntities')
            ->with(
                [
                    'filter' => [
                        'force' => [
                            [
                                'column' => 'IDENTITY(w.webhook)',
                                'expr'   => 'eq',
                                'value'  => 1,
                            ],
                        ],
                    ],
                    'limit'         => 0,
                    'iterable_mode' => true,
                    'start'         => 0,
                    'orderBy'       => 'w.id',
                    'orderByDir'    => 'ASC',
                ]
            );
        $this->initModel()->getWebhookQueues($webhook);
    }

    public function testMinAndMaxQueueIdWhenBothSet(): void
    {
        $webhook = new class extends Webhook {
            public function getId(): int
            {
                return 1;
            }
        };

        $webhook->setEventsOrderbyDir('ASC');

        $this->entityManagerMock->expects($this->once())
            ->method('getRepository')
            ->with(WebhookQueue::class)
            ->willReturn($this->webhookQueueRepository);

        $this->webhookQueueRepository->method('getTableAlias')->willReturn('w');

        $this->webhookQueueRepository->expects($this->once())
            ->method('getEntities')
            ->with(
                [
                    'filter' => [
                        'force' => [
                            [
                                'column' => 'IDENTITY(w.webhook)',
                                'expr'   => 'eq',
                                'value'  => 1,
                            ],
                            [
                                'column' => 'w.id',
                                'expr'   => 'gte',
                                'value'  => 20,
                            ],
                            [
                                'column' => 'w.id',
                                'expr'   => 'lte',
                                'value'  => 30,
                            ],
                        ],
                    ],
                    'iterable_mode' => true,
                    'orderBy'       => 'w.id',
                    'orderByDir'    => 'ASC',
                ]
            );

        $model = $this->initModel();
        $model->setMinQueueId(20);
        $model->setMaxQueueId(30);
        $model->getWebhookQueues($webhook);
    }

    private function initModel(): WebhookModel
    {
        $model = new WebhookModel(
            $this->parametersHelperMock,
            $this->serializerMock,
            $this->httpClientMock,
            $this->entityManagerMock,
            $this->createMock(CorePermissions::class),
            $this->eventDispatcherMock,
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(Translator::class),
            $this->userHelper,
            $this->createMock(LoggerInterface::class)
        );

        return $model;
    }
}
