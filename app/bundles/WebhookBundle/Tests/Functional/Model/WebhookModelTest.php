<?php

namespace Mautic\WebhookBundle\Tests\Functional\Model;

use Doctrine\Common\Collections\Order;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookQueue;
use Mautic\WebhookBundle\Model\WebhookModel;
use PHPUnit\Framework\Assert;

final class WebhookModelTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Cleanup from previous tests
        $this->connection->executeStatement('DELETE FROM '.MAUTIC_TABLE_PREFIX.'webhook_queue');
        $this->connection->executeStatement('ALTER TABLE '.MAUTIC_TABLE_PREFIX.'webhook_queue AUTO_INCREMENT = 1');
    }

    public function testEventsOrderByDirAsc(): void
    {
        $webhookModel = $this->getWebhookModel(Order::Ascending->value);
        $webhook      = $this->createWebhookAndQueue();
        $queueArray   = $webhookModel->getWebhookQueues($webhook);

        // Order should be 1 to 10
        $counter = 1;

        foreach ($queueArray as $queuedEvent) {
            Assert::assertSame($counter, $queuedEvent->getId());

            $payload = json_decode($queuedEvent->getPayload(), true);
            Assert::assertSame($counter, $payload['spoof']);

            ++$counter;
        }

        Assert::assertSame(11, $counter);
    }

    public function testEventsOrderByDirDesc(): void
    {
        $webhookModel = $this->getWebhookModel(Order::Descending->value);
        $webhook      = $this->createWebhookAndQueue();
        $queueArray   = $webhookModel->getWebhookQueues($webhook);

        // Order should be 10 to 1
        $counter = 10;
        foreach ($queueArray as $queuedEvent) {
            Assert::assertSame($counter, $queuedEvent->getId());

            $payload = json_decode($queuedEvent->getPayload(), true);
            Assert::assertSame($counter, $payload['spoof']);

            --$counter;
        }

        Assert::assertSame(0, $counter);
    }

    private function createWebhookAndQueue(): Webhook
    {
        $webhook = new Webhook();

        $webhook->setName('Test Webhook');
        $webhook->setWebhookUrl('https://localhost');
        $webhook->setSecret('abc13');
        $this->em->persist($webhook);
        $this->em->flush();

        $event = new Event();
        $event->setWebhook($webhook);
        $event->setEventType('mautic.email_on_send');
        $this->em->persist($event);
        $this->em->flush();

        $counter = 1;
        while ($counter <= 10) {
            $this->createWebhookQueue($webhook, $event, ['spoof' => $counter]);

            ++$counter;
        }

        return $webhook;
    }

    /**
     * @param mixed[] $payload
     */
    private function createWebhookQueue(Webhook $webhook, Event $event, array $payload): void
    {
        $queue = new WebhookQueue();
        $queue->setDateAdded(new \DateTime());
        $queue->setEvent($event);
        $queue->setWebhook($webhook);
        $queue->setPayload(json_encode($payload));
        $this->em->persist($queue);
        $this->em->flush();
    }

    private function getWebhookModel(string $direction): WebhookModel
    {
        $webhookParams = [
            'queue_mode'         => WebhookModel::COMMAND_PROCESS,
            'events_orderby_dir' => $direction,
        ];

        $this->setUpSymfony($webhookParams);

        return static::getContainer()->get('mautic.webhook.model.webhook');
    }
}
