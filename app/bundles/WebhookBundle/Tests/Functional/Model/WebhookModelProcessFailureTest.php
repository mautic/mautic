<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Functional\Model;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\UserBundle\Entity\User;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\Log;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Model\WebhookModel;
use PHPUnit\Framework\Assert;

final class WebhookModelProcessFailureTest extends MauticMysqlTestCase
{
    /**
     * @var WebhookModel
     */
    private $webhookModel;

    /**
     * @var MockHandler
     */
    private $clientMockHandler;

    protected function setUp(): void
    {
        $this->configParams['queue_mode']             = WebhookModel::IMMEDIATE_PROCESS;
        $this->configParams['disable_auto_unpublish'] = 'testDisableAutoUnpublishIsEnabled' === $this->name();
        parent::setUp();

        $this->webhookModel                = self::$kernel->getContainer()->get('mautic.webhook.model.webhook');
        $this->clientMockHandler           = new MockHandler();
    }

    /**
     * @param array<int> $logStatusCodes
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataFailureWithPreviousLogs')]
    public function testFailureWithPreviousLogs(array $logStatusCodes, bool $expectedIsPublished, int $expectedNumberOfLogs): void
    {
        $this->clientMockHandler->append(new Response(401));
        $webhook = $this->createWebhook();
        $webhook->setUnHealthySince(new \DateTimeImmutable());
        foreach ($logStatusCodes as $logStatusCode) {
            $this->createWebhookLog($webhook, $logStatusCode);
        }

        $this->em->flush();
        $this->processWebhook($webhook);

        Assert::assertSame($expectedIsPublished, $webhook->getIsPublished());
        $this->assertNumberOfLogs($expectedNumberOfLogs);
    }

    /**
     * @return iterable<mixed>
     */
    public static function dataFailureWithPreviousLogs(): iterable
    {
        yield 'no previous logs' => [[], true, 1];
        yield 'at least one successful previous log' => [[200, 403], true, 3];
        yield 'all failed previous logs' => [[401, 403], false, 3];
    }

    public function test404DoesNotProduceRedundantLog(): void
    {
        $this->clientMockHandler->append(new Response(404));

        $webhook = $this->createWebhook();
        $webhook->setUnHealthySince(new \DateTimeImmutable());
        $this->createWebhookLog($webhook, 401);

        $this->em->flush();
        $this->processWebhook($webhook);

        Assert::assertFalse($webhook->getIsPublished());
        $this->assertNumberOfLogs(2);
    }

    public function testWebhookIsNotUnpublishedIfModifiedRecently(): void
    {
        $webhook = $this->createWebhook();
        $webhook->setDateModified(new \DateTime('-1 day'));
        $this->createWebhookLog($webhook, 401);

        $this->em->flush();
        $this->processWebhook($webhook);

        Assert::assertTrue($webhook->getIsPublished());
        $this->assertNumberOfLogs(2);
    }

    public function testWebhookIsUnpublishedIfNotModifiedRecently(): void
    {
        $webhook = $this->createWebhook();
        $webhook->setUnHealthySince(new \DateTimeImmutable());
        $this->createWebhookLog($webhook, 401);

        $this->em->flush();
        $this->processWebhook($webhook);

        Assert::assertFalse($webhook->getIsPublished());
        $this->assertNumberOfLogs(2);
    }

    public function testDisableAutoUnpublishIsEnabled(): void
    {
        $webhook = $this->createWebhook();
        $this->createWebhookLog($webhook, 401);

        $this->em->flush();
        $this->processWebhook($webhook);

        Assert::assertTrue($webhook->getIsPublished());
        $this->assertNumberOfLogs(2);
    }

    private function createWebhook(): Webhook
    {
        $user = $this->em->getRepository(User::class)->findOneBy([]);

        $webhook = new Webhook();
        $webhook->setCreatedBy($user);
        $webhook->setName('Test');
        $webhook->setWebhookUrl('https://domain.tld');
        $webhook->setSecret('secret');
        $webhook->setDateModified(new \DateTime('-1 week'));
        $this->em->persist($webhook);
        $this->em->flush();
        $webhook->setChanges([]);

        return $webhook;
    }

    private function createWebhookEvent(Webhook $webhook): Event
    {
        $event = new Event();
        $event->setWebhook($webhook);
        $event->setEventType('type');
        $this->em->persist($event);

        return $event;
    }

    private function createWebhookLog(Webhook $webhook, int $statusCode): void
    {
        $log = new Log();
        $log->setWebhook($webhook);
        $log->setStatusCode($statusCode);
        $this->em->persist($log);
    }

    private function processWebhook(Webhook $webhook): void
    {
        $event = $this->createWebhookEvent($webhook);
        $queue = $this->webhookModel->queueWebhook($webhook, $event, []);
        $this->webhookModel->processWebhook($webhook, $queue);
    }

    private function assertNumberOfLogs(int $expectedNumberOfLogs): void
    {
        Assert::assertSame($expectedNumberOfLogs, $this->em->getRepository(Log::class)->count([]));
    }
}
