<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\Log;
use Mautic\WebhookBundle\Entity\Webhook;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class WebhookControllerTest extends MauticMysqlTestCase
{
    public function testViewWebhookDetail(): void
    {
        $webhook = $this->createWebhook('test', 'http://domain.tld', 'secret');
        $this->createWebhookEvent($webhook, 'Type');
        for ($log = 1; $log <= 105; ++$log) {
            $this->createWebhookLog($webhook, 'test', 200);
        }
        $this->em->flush();
        $this->em->clear();
        $crawler = $this->client->request(Request::METHOD_GET, '/s/webhooks/view/'.$webhook->getId());
        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());

        $logList = $crawler->filter('.table.table-responsive > tbody > tr')->count();
        Assert::assertSame(Webhook::LOGS_DISPLAY_LIMIT, $logList);
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

        return $log;
    }
}
