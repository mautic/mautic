<?php

namespace Mautic\WebhookBundle\Tests\Functional;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mautic\CoreBundle\Entity\Notification;
use Mautic\CoreBundle\Entity\NotificationRepository;
use Mautic\CoreBundle\Test\Guzzle\ClientMockTrait;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\WebhookBundle\Command\ProcessWebhookQueuesCommand;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookQueue;
use Mautic\WebhookBundle\Entity\WebhookQueueRepository;
use Mautic\WebhookBundle\Entity\WebhookRepository;
use Mautic\WebhookBundle\Model\WebhookModel;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookFunctionalTest extends MauticMysqlTestCase
{
    use ClientMockTrait;

    protected $useCleanupRollback = false;

    /**
     * @var WebhookQueueRepository
     */
    private $webhookQueueRepository;

    /**
     * @var NotificationRepository
     */
    private $notificationRepository;

    /**
     * @var WebhookRepository|EntityRepository<Webhook>
     */
    private $webhhokRepository;

    protected function setUp(): void
    {
        $this->authenticateApi = true;
        parent::setUp();

        $this->setUpSymfony(
            $this->configParams +
            [
                'queue_mode'    => WebhookModel::COMMAND_PROCESS,
                'webhook_limit' => 2,
            ]
        );

        $this->truncateTables('leads', 'webhooks', 'webhook_queue', 'webhook_events');

        $this->webhookQueueRepository       = $this->em->getRepository(WebhookQueue::class);
        $this->notificationRepository       = $this->em->getRepository(Notification::class);
        $this->webhhokRepository            = $this->em->getRepository(Webhook::class);
    }

    /**
     * Clean up after the tests.
     */
    protected function beforeTearDown(): void
    {
        $this->truncateTables('leads', 'webhooks', 'webhook_queue', 'webhook_events');
    }

    public function testWebhookWorkflowWithCommandProcess(): void
    {
        $webhookQueueRepository = $this->em->getRepository(WebhookQueue::class);
        \assert($webhookQueueRepository instanceof WebhookQueueRepository);
        $this->mockSuccessfulWebhookResponse(2);
        $webhook = $this->createWebhook();
        // Ensure we have a clean slate. There should be no rows waiting to be processed at this point.
        Assert::assertSame(0, $this->getQueueCountByWebhookId($webhook->getId()));

        $this->createContacts();

        // At this point there should be 3 events waiting to be processed.
        Assert::assertSame(3, $this->getQueueCountByWebhookId($webhook->getId()));

        $this->testSymfonyCommand(ProcessWebhookQueuesCommand::COMMAND_NAME, ['--webhook-id' => $webhook->getId()]);

        // The queue should be processed now.
        Assert::assertSame(0, $this->getQueueCountByWebhookId($webhook->getId()));
    }

    public function testWebhookWorkflowWithCommandProcessInQueueRange(): void
    {
        $this->mockSuccessfulWebhookResponse(2);
        $webhook  = $this->createWebhook();
        $contacts = $this->createContacts();

        $this->testSymfonyCommand(ProcessWebhookQueuesCommand::COMMAND_NAME, [
            '--webhook-id' => $webhook->getId(),
            '--min-id'     => $contacts[0],
            '--max-id'     => $contacts[2],
        ]);

        // The queue should be processed now.
        Assert::assertSame(0, $this->getQueueCountByWebhookId($webhook->getId()));
    }

    public function testWebhookWorkflowWithCommandProcessWithoutPassingWebhookID(): void
    {
        $this->mockSuccessfulWebhookResponse(2);
        $webhook = $this->createWebhook();
        $this->createContacts();

        $this->testSymfonyCommand(ProcessWebhookQueuesCommand::COMMAND_NAME);

        // The queue should be processed now.
        Assert::assertSame(0, $this->getQueueCountByWebhookId($webhook->getId()));
    }

    /**
     * @return iterable<mixed>
     */
    public static function dataNotificationToUser(): iterable
    {
        yield 'Support User' => [null, 1];
        yield 'Actual user' => [1, 1];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('dataNotificationToUser')]
    public function testWebhookFailureNotificationSent(?int $createdByUserId, ?int $expectedUserId): void
    {
        $this->mockFailedWebhookResponse(2);
        $webhook = $this->createWebhook();
        $webhook->setCreatedBy();
        $webhook->setModifiedBy();
        $this->em->persist($webhook);
        $this->em->flush();
        $this->createContacts();

        $this->testSymfonyCommand(ProcessWebhookQueuesCommand::COMMAND_NAME, ['--webhook-id' => $webhook->getId()]);

        Assert::assertSame(3, $this->getQueueCountByWebhookId($webhook->getId()));

        $webhookQueues = $this->getWebhookQueue($webhook->getId());
        foreach ($webhookQueues as $webhookQueue) {
            $webhookQueue->setRetries(2);
            $webhookQueue->setDateModified((new \DateTimeImmutable())->modify('-3601 seconds'));
            $this->em->persist($webhookQueue);
            $this->em->flush();
        }

        $webhook->setCreatedBy($createdByUserId);
        $webhook->setModifiedBy($createdByUserId);
        $webhook->setUnHealthySince((new \DateTimeImmutable())->modify('-3601 seconds'));
        $webhook->setMarkedUnhealthyAt((new \DateTimeImmutable())->modify('-3601 seconds'));

        $this->testSymfonyCommand(ProcessWebhookQueuesCommand::COMMAND_NAME, ['--webhook-id' => $webhook->getId()]);

        Assert::assertCount(1, $this->notificationRepository->getNotifications($expectedUserId));
        Assert::assertSame(3, $this->getQueueCountByWebhookId($webhook->getId()));

        $this->testSymfonyCommand(ProcessWebhookQueuesCommand::COMMAND_NAME);

        $webhook = $this->webhhokRepository->find($webhook->getId());
        Assert::assertNotNull($webhook->getMarkedUnhealthyAt());
        Assert::assertNotNull($webhook->getUnHealthySince());
        Assert::assertNotNull($webhook->getLastNotificationSentAt());
    }

    public function testWebhookQueueNotProcessedIfMarkedUnhealthy(): void
    {
        $this->mockSuccessfulWebhookResponse();
        $webhook = $this->createWebhook();
        $webhook->setMarkedUnhealthyAt(new \DateTimeImmutable());
        $this->em->persist($webhook);
        $this->em->flush();
        $this->createContacts();

        $this->testSymfonyCommand(ProcessWebhookQueuesCommand::COMMAND_NAME);

        // The queue should not be processed.
        Assert::assertSame(3, $this->getQueueCountByWebhookId($webhook->getId()));
    }

    public function testWebhookQueueProcessedWhenUnhealthyTimePassed(): void
    {
        $this->mockSuccessfulWebhookResponse(2);
        $webhook = $this->createWebhook();
        $webhook->setMarkedUnhealthyAt((new \DateTimeImmutable())->modify('-301 seconds'));
        $this->em->persist($webhook);
        $this->em->flush();
        $this->createContacts();

        $this->testSymfonyCommand(ProcessWebhookQueuesCommand::COMMAND_NAME);

        $webhook = $this->webhhokRepository->find($webhook->getId());
        Assert::assertNull($webhook->getMarkedUnhealthyAt());
        Assert::assertNull($webhook->getUnHealthySince());
        Assert::assertNull($webhook->getLastNotificationSentAt());

        // The queue should be processed.
        Assert::assertSame(0, $this->getQueueCountByWebhookId($webhook->getId()));
    }

    private function createWebhook(): Webhook
    {
        $webhook = new Webhook();
        $event   = new Event();

        $event->setEventType('mautic.lead_post_save_new');
        $event->setWebhook($webhook);

        $webhook->addEvent($event);
        $webhook->setName('Webhook from a functional test');
        $webhook->setWebhookUrl('https://httpbin.org/post');
        $webhook->setSecret('any_secret_will_do');
        $webhook->isPublished(true);
        $webhook->setCreatedBy(1);

        $this->em->persist($event);
        $this->em->persist($webhook);
        $this->em->flush();

        return $webhook;
    }

    /**
     * Creating some contacts via API so all the listeners are triggered.
     * It's closer to a real world contact creation.
     */
    private function createContacts(): array
    {
        $contacts = [
            [
                'email'     => sprintf('contact1%s@email.com', mt_rand(99999, 999999)),
                'firstname' => 'Contact',
                'lastname'  => 'One',
                'points'    => 4,
                'city'      => 'Houston',
                'state'     => 'Texas',
                'country'   => 'United States',
            ],
            [
                'email'     => sprintf('contact2%s@email.com', mt_rand(99999, 999999)),
                'firstname' => 'Contact',
                'lastname'  => 'Two',
                'city'      => 'Boston',
                'state'     => 'Massachusetts',
                'country'   => 'United States',
                'timezone'  => 'America/New_York',
            ],
            [
                'email'     => sprintf('contact3%s@email.com', mt_rand(99999, 999999)),
                'firstname' => 'contact',
                'lastname'  => 'Three',
            ],
        ];

        $this->client->request(Request::METHOD_POST, '/api/contacts/batch/new', $contacts);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        Assert::assertEquals(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        Assert::assertEquals(Response::HTTP_CREATED, $response['statusCodes'][0], $clientResponse->getContent());
        Assert::assertEquals(Response::HTTP_CREATED, $response['statusCodes'][1], $clientResponse->getContent());
        Assert::assertEquals(Response::HTTP_CREATED, $response['statusCodes'][2], $clientResponse->getContent());

        return [
            $response['contacts'][0]['id'],
            $response['contacts'][1]['id'],
            $response['contacts'][2]['id'],
        ];
    }

    private function mockSuccessfulWebhookResponse(int $expectedToBeCalled = 0): void
    {
        $handlerStack = $this->getClientMockHandler();
        for (; $expectedToBeCalled > 0; --$expectedToBeCalled) {
            $handlerStack->append(
                function (RequestInterface $request) use (&$sendRequestCounter) {
                    Assert::assertSame('/post', $request->getUri()->getPath());
                    $jsonPayload = json_decode($request->getBody()->getContents(), true);
                    Assert::assertNotEmpty($request->getHeader('Webhook-Signature'));

                    ++$sendRequestCounter;

                    return new GuzzleResponse(Response::HTTP_OK);
                }
            );
        }
    }

    private function mockFailedWebhookResponse(int $expectedToBeCalled = 0): void
    {
        $handlerStack = $this->getClientMockHandler();
        for (; $expectedToBeCalled > 0; --$expectedToBeCalled) {
            $handlerStack->append(
                function (RequestInterface $request) use (&$sendRequestCounter) {
                    Assert::assertSame('/post', $request->getUri()->getPath());
                    $jsonPayload = json_decode($request->getBody()->getContents(), true);
                    Assert::assertNotEmpty($request->getHeader('Webhook-Signature'));

                    ++$sendRequestCounter;

                    return new GuzzleResponse(Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            );
        }
    }

    private function getWebhookQueue(int $webhookId): Paginator
    {
        return $this->webhookQueueRepository->getEntities([
            'webhook_id' => $webhookId,
        ]);
    }

    private function getQueueCountByWebhookId(int $webhookId): int
    {
        return $this->webhookQueueRepository->count([
            'webhook' => $webhookId,
        ]);
    }
}
