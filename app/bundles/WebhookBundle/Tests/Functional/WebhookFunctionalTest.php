<?php

namespace Mautic\WebhookBundle\Tests\Functional;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\WebhookBundle\Command\ProcessWebhookQueuesCommand;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookQueue;
use Mautic\WebhookBundle\Entity\WebhookQueueRepository;
use Mautic\WebhookBundle\Model\WebhookModel;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        $this->authenticateApi = true;
        parent::setUp();

        $this->setUpSymfony(
            $this->configParams +
            [
                'queue_mode' => WebhookModel::COMMAND_PROCESS,
            ]
        );

        $this->truncateTables('leads', 'webhooks', 'webhook_queue', 'webhook_events');
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
        $sendRequestCounter = 0;

        $handlerStack = static::getContainer()->get(MockHandler::class);

        // One resource is going to be found in the Transifex project:
        $handlerStack->append(
            function (RequestInterface $request) use (&$sendRequestCounter) {
                Assert::assertSame('/post', $request->getUri()->getPath());
                $jsonPayload = json_decode($request->getBody()->getContents(), true);
                Assert::assertCount(3, $jsonPayload['mautic.lead_post_save_new']);
                Assert::assertNotEmpty($request->getHeader('Webhook-Signature'));

                ++$sendRequestCounter;

                return new GuzzleResponse(Response::HTTP_OK);
            }
        );

        $webhookQueueRepository = $this->em->getRepository(WebhookQueue::class);
        \assert($webhookQueueRepository instanceof WebhookQueueRepository);

        $webhook = $this->createWebhook();

        // Ensure we have a clean slate. There should be no rows waiting to be processed at this point.
        Assert::assertfalse($webhookQueueRepository->exists($webhook->getId()));

        $this->createContacts();

        // At this point there should be 3 events waiting to be processed.
        Assert::assertTrue($webhookQueueRepository->exists($webhook->getId()));

        $this->testSymfonyCommand(ProcessWebhookQueuesCommand::COMMAND_NAME, ['--webhook-id' => $webhook->getId()]);

        // The queue should be processed now.
        Assert::assertfalse($webhookQueueRepository->exists($webhook->getId()));
        Assert::assertSame(1, $sendRequestCounter);
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
                'email'     => 'contact1@email.com',
                'firstname' => 'Contact',
                'lastname'  => 'One',
                'points'    => 4,
                'city'      => 'Houston',
                'state'     => 'Texas',
                'country'   => 'United States',
            ],
            [
                'email'     => 'contact2@email.com',
                'firstname' => 'Contact',
                'lastname'  => 'Two',
                'city'      => 'Boston',
                'state'     => 'Massachusetts',
                'country'   => 'United States',
                'timezone'  => 'America/New_York',
            ],
            [
                'email'     => 'contact3@email.com',
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
}
