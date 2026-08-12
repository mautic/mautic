<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\Response;
use JMS\Serializer\SerializerInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\EventRepository;
use Mautic\WebhookBundle\Entity\LogRepository;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookQueue;
use Mautic\WebhookBundle\Entity\WebhookQueueRepository;
use Mautic\WebhookBundle\Entity\WebhookRepository;
use Mautic\WebhookBundle\Http\Client;
use Mautic\WebhookBundle\Model\WebhookModel;
use Mautic\WebhookBundle\Service\WebhookService;
use Monolog\Logger;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Generator\UrlGenerator;

final class WebhookModelTest extends TestCase
{
    /**
     * @var MockObject&CoreParametersHelper
     */
    private MockObject $parametersHelperMock;

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
    private MockObject $webhookQueueRepository;

    private WebhookModel $model;

    /**
     * @var MockObject&Client
     */
    private MockObject $httpClientMock;

    protected function setUp(): void
    {
        $this->parametersHelperMock   = $this->createMock(CoreParametersHelper::class);
        $this->entityManagerMock      = $this->createMock(EntityManager::class);
        $this->webhookRepository      = $this->createMock(WebhookRepository::class);
        $this->webhookQueueRepository = $this->createMock(WebhookQueueRepository::class);
        $this->httpClientMock         = $this->createMock(Client::class);

        $this->model                  = $this->initModel();
    }

    public function testSaveEntity(): void
    {
        $entity = new Webhook();

        // The secret hash is null at first.
        $this->assertNull($entity->getSecret());

        $this->webhookRepository->expects($this->once())
            ->method('saveEntity')
            ->willReturnCallback(function (Webhook $entity): void {
                // The secret hash is not empty on save.
                $this->assertNotEmpty($entity->getSecret());
            });

        $this->model->saveEntity($entity);
    }

    public function testGetEventsOrderbyDirWhenSetInWebhook(): void
    {
        $webhook = new Webhook()->setEventsOrderbyDir('DESC');
        $this->assertEquals('DESC', $this->model->getEventsOrderbyDir($webhook));
    }

    public function testGetEventsOrderbyDirWhenNotSetInWebhook(): void
    {
        $this->parametersHelperMock->expects($this->exactly(9))->method('get')->willReturn('DESC');
        $this->assertEquals('DESC', $this->initModel()->getEventsOrderbyDir());
    }

    public function testGetEventsOrderbyDirWhenWebhookNotProvided(): void
    {
        $this->parametersHelperMock->expects($this->exactly(9))->method('get')->willReturn('DESC');
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
        $queueMock->expects($this->once())->method('getPayload')->willReturn('{"the": "payload"}');
        $queueMock->expects($this->once())->method('getEvent')->willReturn($event);
        $queueMock->expects($this->once())->method('getDateAdded')->willReturn(new \DateTime('2018-04-10T15:04:57+00:00'));
        $queueMock->expects($this->exactly(2))->method('getId')->willReturn('12');

        $this->parametersHelperMock->expects($this->exactly(9))->method('get')
            ->willReturnCallback(function ($param): string|int|null {
                if ('queue_mode' === $param) {
                    return WebhookModel::COMMAND_PROCESS;
                }
                if ('webhook_retry_delay' === $param) {
                    return 3600;
                }

                return null;
            });

        $this->entityManagerMock->expects($this->once())
            ->method('detach')
            ->with($queueMock);

        $this->webhookQueueRepository->expects($this->once())
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

        $this->parametersHelperMock->expects($this->exactly(9))->method('get')
            ->willReturnCallback(function ($param): ?string {
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
        $webhook = new class() extends Webhook {
            public function getId(): int
            {
                return 1;
            }
        };
        $webhook->setWebhookUrl('test-webhook.com');

        $event = new Event();
        $event->setEventType('mautic.email_on_send');

        $queue = new class() extends WebhookQueue {
            public function getId(): string
            {
                return '1';
            }
        };
        $queue->setPayload('{"payload": "some data"}');
        $queue->setEvent($event);
        $queue->setDateAdded(new \DateTime('2021-04-01T16:00:00+00:00'));

        $this->webhookQueueRepository->expects($this->exactly(2))
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
        $this->httpClientMock->expects($this->once())
            ->method('post')
            ->with('test-webhook.com', $responsePayload)
            ->willReturn(new Response(200, [], 'Success'));

        $this->assertTrue($this->model->processWebhook($webhook, $queue));
    }

    public function testMinAndMaxQueueIdWhenNoneIsSet(): void
    {
        $webhook = new class() extends Webhook {
            public function getId(): int
            {
                return 1;
            }
        };

        $webhook->setEventsOrderbyDir('ASC');

        $this->webhookQueueRepository->expects($this->exactly(6))->method('getTableAlias')->willReturn('w');

        $webhookRetryTime = new \DateTimeImmutable()
            ->format(DateTimeHelper::FORMAT_DB);
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
                        'where' => [
                            [
                                'expr' => 'andX',
                                'val'  => [
                                    [
                                        'expr' => 'orX',
                                        'val'  => [
                                            [
                                                'column' => 'w.retries',
                                                'expr'   => 'eq',
                                                'value'  => 0,
                                            ],
                                            [
                                                'expr' => 'andX',
                                                'val'  => [
                                                    [
                                                        'column' => 'w.retries',
                                                        'expr'   => 'gt',
                                                        'value'  => 0,
                                                    ],
                                                    [
                                                        'column' => 'w.dateModified',
                                                        'expr'   => 'lt',
                                                        'value'  => $webhookRetryTime,
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'limit'         => 0,
                    'iterable_mode' => true,
                    'start'         => 0,
                    'orderBy'       => 'w.retries,w.id',
                    'orderByDir'    => 'ASC',
                ]
            );
        $this->initModel()->getWebhookQueues($webhook);
    }

    public function testMinAndMaxQueueIdWhenBothSet(): void
    {
        $webhook = new class() extends Webhook {
            public function getId(): int
            {
                return 1;
            }
        };

        $webhook->setEventsOrderbyDir('ASC');

        $this->webhookQueueRepository->expects($this->exactly(8))->method('getTableAlias')->willReturn('w');
        $webhookRetryTime = new \DateTimeImmutable()
            ->format(DateTimeHelper::FORMAT_DB);
        $expected = [
            'iterable_mode' => true,
            'orderBy'       => 'w.retries,w.id',
            'orderByDir'    => 'ASC',
            'filter'        => [
                'force' => [
                    [
                        'column' => 'IDENTITY(w.webhook)',
                        'expr'   => 'eq',
                        'value'  => 1,
                    ],
                ],
                'where' => [
                    [
                        'expr' => 'andX',
                        'val'  => [
                            [
                                'expr' => 'orX',
                                'val'  => [
                                    [
                                        'column' => 'w.retries',
                                        'expr'   => 'eq',
                                        'value'  => 0,
                                    ],
                                    [
                                        'expr' => 'andX',
                                        'val'  => [
                                            [
                                                'column' => 'w.retries',
                                                'expr'   => 'gt',
                                                'value'  => 0,
                                            ],
                                            [
                                                'column' => 'w.dateModified',
                                                'expr'   => 'lt',
                                                'value'  => $webhookRetryTime,
                                            ],
                                        ],
                                    ],
                                ],
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
                ],
            ],
        ];
        $this->webhookQueueRepository->expects($this->once())
            ->method('getEntities')
            ->with($expected);

        $model = $this->initModel();
        $model->setMinQueueId(20);
        $model->setMaxQueueId(30);
        $model->getWebhookQueues($webhook);
    }

    private function initModel(): WebhookModel
    {
        // create anew webhook model instance using mocks
        $model              = new WebhookModel(
            $this->parametersHelperMock,
            $this->createStub(SerializerInterface::class),
            $this->httpClientMock,
            $this->entityManagerMock,
            $this->createStub(CorePermissions::class),
            $this->createStub(EventDispatcher::class),
            $this->createStub(UrlGenerator::class),
            $this->createStub(Translator::class),
            $this->createStub(UserHelper::class),
            $this->createStub(Logger::class),
            $this->createStub(WebhookService::class),
            $this->webhookRepository, // $webhookRepository
            $this->webhookQueueRepository, // $webhookQueueRepository
            $this->createStub(EventRepository::class), // $eventRepository
            $this->createStub(LogRepository::class), // $logRepository
        );

        return $model;
    }
}
