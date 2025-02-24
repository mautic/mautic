<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Functional\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\WebhookBundle\Command\DeleteWebhookLogsCommand;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\Log;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Model\WebhookModel;
use PHPUnit\Framework\Assert;

final class DeleteWebhookLogsCommandTest extends MauticMysqlTestCase
{
    /**
     * @var WebhookModel
     */
    private $webhookModel;

    protected function setUp(): void
    {
        $this->configParams['clean_webhook_logs_in_background']     = 'testRemoveLogUsingCleanUpJob' === $this->getName();
        $this->configParams['webhook_log_max']                      = 5;
        parent::setUp();

        $this->webhookModel = static::getContainer()->get('mautic.webhook.model.webhook');
    }

    public function testRemoveLogInstantly(): void
    {
        $webhook = $this->createWebhook('test', 'http://domain.tld', 'secret');
        $this->createWebhookEvent($webhook, 'Type');
        $logIds = [];
        for ($log = 1; $log <= 6; ++$log) {
            $addedLog = $this->createWebhookLog($webhook, 'test', 200);
            array_push($logIds, $addedLog->getId());
        }

        $this->assertLogs($webhook, 6, $logIds);

        $this->webhookModel->addLog($webhook, 200, 15);

        array_shift($logIds);
        array_push($logIds, end($logIds) + 1);
        $this->assertLogs($webhook, 6, $logIds);
    }

    public function testRemoveLogUsingCleanUpJob(): void
    {
        $webhook = $this->createWebhook('test', 'http://domain.tld', 'secret');
        $this->createWebhookEvent($webhook, 'Type');
        $logIds = [];
        for ($log = 1; $log <= 6; ++$log) {
            $addedLog = $this->createWebhookLog($webhook, 'test', 200);
            array_push($logIds, $addedLog->getId());
        }

        $this->assertLogs($webhook, 6, $logIds);

        $this->webhookModel->addLog($webhook, 200, 15);

        array_push($logIds, end($logIds) + 1);
        $this->assertLogs($webhook, 7, $logIds);
    }

    public function testRemoveLogCommand(): void
    {
        $webhook = $this->createWebhook('test', 'http://domain.tld', 'secret');
        $this->createWebhookEvent($webhook, 'Type');
        $logIds = [];
        for ($log = 1; $log <= 7; ++$log) {
            $addedLog = $this->createWebhookLog($webhook, 'test', 200);
            array_push($logIds, $addedLog->getId());
        }

        $output = $this->testSymfonyCommand(DeleteWebhookLogsCommand::COMMAND_NAME);
        Assert::assertStringContainsString('2 logs deleted successfully for webhook id - '.$webhook->getId(), $output->getDisplay());
        array_shift($logIds);
        array_shift($logIds);
        $this->assertLogs($webhook, 5, $logIds);
    }

    public function testRemoveLogCommandForNoWebhook(): void
    {
        $output = $this->testSymfonyCommand(DeleteWebhookLogsCommand::COMMAND_NAME);
        Assert::assertStringContainsString('There is 0 webhooks with logs more than defined limit.', $output->getDisplay());
    }

    /**
     * @param int[] $expectedIds
     */
    private function assertLogs(Webhook $webhook, int $expectedCount, array $expectedIds): void
    {
        $logs   = $this->em->getRepository(Log::class)->findBy(['webhook' => $webhook]);
        $logIds = array_map(fn (Log $log) => $log->getId(), $logs);

        Assert::assertCount($expectedCount, $logs);
        Assert::assertSame($expectedIds, $logIds);
    }

    private function createWebhook(string $name, string $url, string $secret): Webhook
    {
        $webhook = new Webhook();
        $webhook->setName($name);
        $webhook->setWebhookUrl($url);
        $webhook->setSecret($secret);
        $this->em->persist($webhook);

        return $webhook;
    }

    private function createWebhookEvent(Webhook $webhook, string $type): Event
    {
        $event = new Event();
        $event->setWebhook($webhook);
        $event->setEventType($type);
        $this->em->persist($event);

        return $event;
    }

    private function createWebhookLog(Webhook $webhook, string $note, int $statusCode): Log
    {
        $log = new Log();
        $log->setWebhook($webhook);
        $log->setNote($note);
        $log->setStatusCode($statusCode);
        $this->em->persist($log);
        $this->em->flush();

        return $log;
    }
}
