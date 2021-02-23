<?php

namespace Mautic\WebhookBundle\Tests\Functional;

use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Http\Adapter\Guzzle6\Client;
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
    protected function setUp(): void
    {
        $this->setUpSymfony(
            [
                'queue_mode' => WebhookModel::COMMAND_PROCESS,
            ]
        );

        $this->truncateTables('leads', 'webhooks', 'webhook_queue', 'webhook_events');
    }

    /**
     * Clean up after the tests.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->truncateTables('leads', 'webhooks', 'webhook_queue', 'webhook_events');
    }

    public function testWebhookWorkflowWithCommandProcess()
    {
        $httpClient                    = new class() extends Client {
            public $sendRequestCounter = 0;

            public function sendRequest(RequestInterface $request)
            {
                Assert::assertSame('://whatever.url', $request->getUri()->getPath());
                $jsonPayload = json_decode($request->getBody()->getContents(), true);
                Assert::assertCount(3, $jsonPayload['mautic.lead_post_save_new']);

                ++$this->sendRequestCounter;

                return new GuzzleResponse(200);
            }
        };

        $this->container->set('mautic.guzzle.client', $httpClient);

        /** @var WebhookQueueRepository $webhookQueueRepository */
        $webhookQueueRepository = $this->em->getRepository(WebhookQueue::class);

        $webhook = $this->createWebhook();

        // Ensure we have a clean slate. There should be no rows waiting to be processed at this point.
        Assert::assertSame(0, $webhookQueueRepository->getQueueCountByWebhookId($webhook->getId()));

        $this->createContacts();

        // At this point there should be 3 events waiting to be processed.
        Assert::assertSame(3, $webhookQueueRepository->getQueueCountByWebhookId($webhook->getId()));

        $this->runCommand(ProcessWebhookQueuesCommand::COMMAND_NAME, ['--webhook-id' => $webhook->getId()]);

        // The queue should be processed now.
        Assert::assertSame(0, $webhookQueueRepository->getQueueCountByWebhookId($webhook->getId()));
        Assert::assertSame(1, $httpClient->sendRequestCounter);
    }

    private function createWebhook(): Webhook
    {
        $webhook = new Webhook();
        $event   = new Event();

        $event->setEventType('mautic.lead_post_save_new');
        $event->setWebhook($webhook);

        $webhook->addEvent($event);
        $webhook->setName('Webhook from a functional test');
        $webhook->setWebhookUrl('https:://whatever.url');
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
