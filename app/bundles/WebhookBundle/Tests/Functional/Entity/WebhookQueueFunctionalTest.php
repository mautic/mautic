<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookQueue;
use PHPUnit\Framework\Assert;
use ReflectionProperty;

class WebhookQueueFunctionalTest extends MauticMysqlTestCase
{
    public function testPayloadBackwardCompatible(): void
    {
        $webhookQueue = $this->createWebhookQueue();

        $payload  = 'BC payload';
        $property = new ReflectionProperty(WebhookQueue::class, 'payload');
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

    private function fetchPayloadDbValues(WebhookQueue $webhookQueue): array
    {
        $prefix = $this->container->getParameter('mautic.db_table_prefix');
        $query  = sprintf('SELECT payload, payload_compressed FROM %swebhook_queue WHERE id = ?', $prefix);

        return $this->connection->executeQuery($query, [$webhookQueue->getId()])
            ->fetch();
    }
}
