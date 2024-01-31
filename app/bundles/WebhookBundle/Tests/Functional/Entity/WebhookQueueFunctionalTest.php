<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookQueue;
use PHPUnit\Framework\Assert;

class WebhookQueueFunctionalTest extends MauticMysqlTestCase
{
    public function testPayloadBackwardCompatible(): void
    {
        $webhookQueue = $this->createWebhookQueue();

        $payload  = 'BC payload';
        $property = new \ReflectionProperty(WebhookQueue::class, 'payload');
        $property->setAccessible(true);
        $property->setValue($webhookQueue, $payload);

        Assert::assertSame($payload, $webhookQueue->getPayload());

        $this->em->flush();

        $payloadDbValues = $this->fetchPayloadDbValues($webhookQueue);
        Assert::assertSame($payload, $payloadDbValues['payload']);
        Assert::assertNull($payloadDbValues['payload_compressed']);

        $this->em->clear();
        $webhookQueue = $this->em->getRepository(WebhookQueue::class)
            ->find($webhookQueue->getId());

        Assert::assertSame($payload, $webhookQueue->getPayload());
    }

    public function testPayloadCompressed(): void
    {
        $webhookQueue = $this->createWebhookQueue();

        $payload  = 'Compressed payload';
        $webhookQueue->setPayload($payload);

        Assert::assertSame($payload, $webhookQueue->getPayload());

        $this->em->flush();

        $payloadDbValues = $this->fetchPayloadDbValues($webhookQueue);
        Assert::assertNull($payloadDbValues['payload']);
        Assert::assertSame($payload, gzuncompress($payloadDbValues['payload_compressed']));

        $this->em->clear();
        $webhookQueue = $this->em->getRepository(WebhookQueue::class)
            ->find($webhookQueue->getId());

        Assert::assertSame($payload, $webhookQueue->getPayload());
    }

    private function createWebhookQueue(): WebhookQueue
    {
        $webhook = new Webhook();
        $webhook->setName('Test');
        $webhook->setWebhookUrl('http://domain.tld');
        $webhook->setSecret('secret');
        $this->em->persist($webhook);

        $even = new Event();
        $even->setWebhook($webhook);
        $even->setEventType('Type');
        $this->em->persist($even);

        $webhookQueue = new WebhookQueue();
        $webhookQueue->setWebhook($webhook);
        $webhookQueue->setEvent($even);
        $this->em->persist($webhookQueue);

        return $webhookQueue;
    }

    /**
     * @return mixed[]
     */
    private function fetchPayloadDbValues(WebhookQueue $webhookQueue): array
    {
        $prefix = static::getContainer()->getParameter('mautic.db_table_prefix');
        $query  = sprintf('SELECT payload, payload_compressed FROM %swebhook_queue WHERE id = ?', $prefix);

        return $this->connection->executeQuery($query, [$webhookQueue->getId()])
            ->fetchAssociative();
    }
}
