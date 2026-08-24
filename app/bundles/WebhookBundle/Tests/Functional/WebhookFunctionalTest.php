<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Functional;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mautic\CoreBundle\Entity\NotificationRepository;
use Mautic\CoreBundle\Test\Guzzle\ClientMockTrait;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use Mautic\WebhookBundle\Command\ProcessWebhookQueuesCommand;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookQueue;
use Mautic\WebhookBundle\Entity\WebhookQueueRepository;
use Mautic\WebhookBundle\Entity\WebhookRepository;
use Mautic\WebhookBundle\Model\WebhookModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class WebhookFunctionalTest extends MauticMysqlTestCase
{
    use ClientMockTrait;

    private const ADMIN_USER = 'admin';

    protected $useCleanupRollback = false;

    private WebhookQueueRepository $webhookQueueRepository;

    private NotificationRepository $notificationRepository;

    /**
     * @var WebhookRepository|EntityRepository<Webhook>
     */
    private \Mautic\WebhookBundle\Entity\WebhookRepository|EntityRepository $webhhokRepository;

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

        $this->webhookQueueRepository       = self::getContainer()->get(WebhookQueueRepository::class);
        $this->notificationRepository       = self::getContainer()->get(NotificationRepository::class);
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
        $this->assertInstanceOf(WebhookQueueRepository::class, $webhookQueueRepository);
        $this->mockSuccessfulWebhookResponse(2);
        $webhook = $this->createWebhook();
        // Ensure we have a clean slate. There should be no rows waiting to be processed at this point.
        $this->assertSame(0, $this->getQueueCountByWebhookId($webhook->getId()));

        $this->createContacts();

        // At this point there should be 3 events waiting to be processed.
        $this->assertSame(3, $this->getQueueCountByWebhookId($webhook->getId()));

        $this->testSymfonyCommand(ProcessWebhookQueuesCommand::COMMAND_NAME, ['--webhook-id' => $webhook->getId()]);

        // The queue should be processed now.
        $this->assertSame(0, $this->getQueueCountByWebhookId($webhook->getId()));
    }

    public function testWebhookWorkflowWithCommandProcessInQueueRange(): void
    {
        $this->mockSuccessfulWebhookResponse(2);
        $webhook = $this->createWebhook();
        $this->createContacts();
        $range = $this->getWebhookQueueRange($webhook->getId());

        // BUG: queue ID should be used, not contact ID
        $this->testSymfonyCommand(ProcessWebhookQueuesCommand::COMMAND_NAME, [
            '--webhook-id' => $webhook->getId(),
            '--min-id'     => $range[0],
            '--max-id'     => $range[1],
        ]);

        // The queue should be processed now.
        $this->assertSame(0, $this->getQueueCountByWebhookId($webhook->getId()));
    }

    public function testWebhookWorkflowWithCommandProcessWithoutPassingWebhookID(): void
    {
        $this->mockSuccessfulWebhookResponse(2);
        $webhook = $this->createWebhook();
        $this->createContacts();

        $this->testSymfonyCommand(ProcessWebhookQueuesCommand::COMMAND_NAME);

        // The queue should be processed now.
        $this->assertSame(0, $this->getQueueCountByWebhookId($webhook->getId()));
    }

    /**
     * @return iterable<mixed>
     */
    public static function dataNotificationToUser(): iterable
    {
        yield 'Support User' => [null, self::ADMIN_USER];
        yield 'Actual user' => [self::ADMIN_USER, self::ADMIN_USER];
    }

    #[DataProvider('dataNotificationToUser')]
    public function testWebhookFailureNotificationSent(?string $createdByUserName, ?string $expectedUserName): void
    {
        // use real user ID
        $createdByUser = is_null($createdByUserName) ? null : $this->getUser($createdByUserName);
        $expectedUser  = $this->getUser($expectedUserName);

        $this->mockFailedWebhookResponse(2);
        $webhook = $this->createWebhook();
        $webhook->setCreatedBy();
        $webhook->setModifiedBy();
        $this->em->persist($webhook);
        $this->em->flush();
        $this->createContacts();

        $this->testSymfonyCommand(ProcessWebhookQueuesCommand::COMMAND_NAME, ['--webhook-id' => $webhook->getId()]);

        $this->assertSame(3, $this->getQueueCountByWebhookId($webhook->getId()));

        $webhookQueues = $this->getWebhookQueue($webhook->getId());
        foreach ($webhookQueues as $webhookQueue) {
            $webhookQueue->setRetries(2);
            $webhookQueue->setDateModified((new \DateTimeImmutable())->modify('-3601 seconds'));
            $this->em->persist($webhookQueue);
            $this->em->flush();
        }

        $createdByUserId = is_null($createdByUser) ? $createdByUser : $createdByUser->getId();

        $webhook->setCreatedBy($createdByUserId);
        $webhook->setModifiedBy($createdByUserId);
        $webhook->setUnHealthySince((new \DateTimeImmutable())->modify('-3601 seconds'));
        $webhook->setMarkedUnhealthyAt((new \DateTimeImmutable())->modify('-3601 seconds'));

        $this->testSymfonyCommand(ProcessWebhookQueuesCommand::COMMAND_NAME, ['--webhook-id' => $webhook->getId()]);

        $this->assertCount(1, $this->notificationRepository->getNotifications($expectedUser->getId()));
        $this->assertSame(3, $this->getQueueCountByWebhookId($webhook->getId()));

        $this->testSymfonyCommand(ProcessWebhookQueuesCommand::COMMAND_NAME);

        $webhook = $this->webhhokRepository->find($webhook->getId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $webhook->getMarkedUnhealthyAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $webhook->getUnHealthySince());
        $this->assertInstanceOf(\DateTimeImmutable::class, $webhook->getLastNotificationSentAt());
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
        $this->assertSame(3, $this->getQueueCountByWebhookId($webhook->getId()));
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
        $this->assertInstanceOf(Webhook::class, $webhook);
        $this->assertNotInstanceOf(\DateTimeImmutable::class, $webhook->getMarkedUnhealthyAt());
        $this->assertNotInstanceOf(\DateTimeImmutable::class, $webhook->getUnHealthySince());
        $this->assertNotInstanceOf(\DateTimeImmutable::class, $webhook->getLastNotificationSentAt());

        // The queue should be processed.
        $this->assertSame(0, $this->getQueueCountByWebhookId($webhook->getId()));
    }

    private function createWebhook(): Webhook
    {
        $user    = $this->getUser(self::ADMIN_USER);
        $webhook = new Webhook();
        $event   = new Event();

        $event->setEventType('mautic.lead_post_save_new');
        $event->setWebhook($webhook);

        $webhook->addEvent($event);
        $webhook->setName('Webhook from a functional test');
        $webhook->setWebhookUrl('https://httpbin.org/post');
        $webhook->setSecret('any_secret_will_do');
        $webhook->isPublished(true);
        $webhook->setCreatedBy($user->getId());

        $this->em->persist($event);
        $this->em->persist($webhook);
        $this->em->flush();

        return $webhook;
    }

    /**
     * Creating some contacts via API so all the listeners are triggered.
     * It's closer to a real world contact creation.
     *
     * @return int[]|string[]
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

        $this->assertEquals(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][0], $clientResponse->getContent());
        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][1], $clientResponse->getContent());
        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][2], $clientResponse->getContent());

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
                function (RequestInterface $request) use (&$sendRequestCounter): GuzzleResponse {
                    $this->assertSame('/post', $request->getUri()->getPath());
                    $jsonPayload = json_decode($request->getBody()->getContents(), true);
                    $this->assertNotEmpty($request->getHeader('Webhook-Signature'));

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
                function (RequestInterface $request) use (&$sendRequestCounter): GuzzleResponse {
                    $this->assertSame('/post', $request->getUri()->getPath());
                    $jsonPayload = json_decode($request->getBody()->getContents(), true);
                    $this->assertNotEmpty($request->getHeader('Webhook-Signature'));

                    ++$sendRequestCounter;

                    return new GuzzleResponse(Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            );
        }
    }

    /**
     * Returns the minimum and maximum queue ID for a given webhook.
     *
     * @return array{0: int, 1: int} // [minId, maxId]
     */
    private function getWebhookQueueRange(int $webhookId): array
    {
        $webhookQueues = $this->getWebhookQueue($webhookId);

        if (0 === count($webhookQueues)) {
            return [0, 0];
        }

        $minId = PHP_INT_MAX;
        $maxId = PHP_INT_MIN;

        foreach ($webhookQueues as $queue) {
            $id = $queue->getId();
            if ($id < $minId) {
                $minId = $id;
            }
            if ($id > $maxId) {
                $maxId = $id;
            }
        }

        return [$minId, $maxId];
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

    private function getUser(string $username): User
    {
        $repository = $this->em->getRepository(User::class);

        return $repository->findOneBy(['username' => $username]);
    }
}
