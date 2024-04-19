<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookQueue;
use Mautic\WebhookBundle\Model\WebhookModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class WebhookSubscriberFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['queue_mode']            = WebhookModel::COMMAND_PROCESS;
        $this->configParams['webhook_email_details'] = 'testWebhooksWithDetailsEnabled' === $this->getName();

        parent::setUp();
    }

    public function testWebhooksWithDetailsEnabled(): void
    {
        $webHookQueues = $this->triggerWebHooks();

        $payloadData = $this->getWebHookPayload($webHookQueues[0]);
        Assert::assertArrayHasKey('content', $payloadData);
        Assert::assertArrayHasKey('tokens', $payloadData);
        $this->assertHasEmailDetailData($payloadData);

        $payloadData = $this->getWebHookPayload($webHookQueues[1]);
        Assert::assertArrayHasKey('stat', $payloadData);

        $statData = $payloadData['stat'];
        Assert::assertIsArray($statData);
        $this->assertHasEmailDetailData($statData);
    }

    public function testWebhooksWithDetailsDisabled(): void
    {
        $webHookQueues = $this->triggerWebHooks();

        $payloadData = $this->getWebHookPayload($webHookQueues[0]);
        Assert::assertArrayNotHasKey('content', $payloadData);
        Assert::assertArrayNotHasKey('tokens', $payloadData);
        $this->assertNotHasEmailDetailData($payloadData);

        $payloadData = $this->getWebHookPayload($webHookQueues[1]);
        Assert::assertArrayHasKey('stat', $payloadData);

        $statData = $payloadData['stat'];
        Assert::assertIsArray($statData);
        $this->assertNotHasEmailDetailData($statData);
    }

    /**
     * @return WebhookQueue[]
     */
    private function triggerWebHooks(): array
    {
        $lead = new Lead();
        $lead->setEmail('mail@test.tld');
        $this->em->persist($lead);

        $leadList = new LeadList();
        $leadList->setName('Segment A');
        $leadList->setPublicName('Segment A');
        $leadList->setAlias('segment-a');
        $this->em->persist($leadList);

        $listLead = new ListLead();
        $listLead->setLead($lead);
        $listLead->setList($leadList);
        $listLead->setDateAdded(new \DateTime());
        $this->em->persist($listLead);

        $email = new Email();
        $email->setName('Email A');
        $email->setSubject('Email A Subject');
        $email->setEmailType('list');
        $email->addList($leadList);
        $this->em->persist($email);

        $webhook = new Webhook();
        $webhook->setName('test');
        $webhook->setWebhookUrl('http://test');
        $webhook->setSecret('secret');
        $this->em->persist($webhook);

        $webhookEvent = new Event();
        $webhookEvent->setWebhook($webhook);
        $webhookEvent->setEventType(EmailEvents::EMAIL_ON_SEND);
        $this->em->persist($webhookEvent);

        $webhookEvent = new Event();
        $webhookEvent->setWebhook($webhook);
        $webhookEvent->setEventType(EmailEvents::EMAIL_ON_OPEN);
        $this->em->persist($webhookEvent);

        $this->em->flush();

        /** @var EmailModel $emailModel */
        $emailModel = static::getContainer()->get('mautic.email.model.email');
        $emailModel->sendEmailToLists($email);

        $stat = $this->em->getRepository(Stat::class)->findOneBy([]);
        $emailModel->hitEmail($stat, new Request(), false, true, new \DateTimeImmutable());

        $webHookQueues = $this->em->getRepository(WebhookQueue::class)->findAll();

        Assert::assertCount(2, $webHookQueues);

        return $webHookQueues;
    }

    /**
     * @return array<string,mixed>
     */
    private function getWebHookPayload(WebhookQueue $webhookQueue): array
    {
        $payload = $webhookQueue->getPayload();
        Assert::assertJson($payload);

        $payloadData = json_decode($payload, true);
        Assert::assertIsArray($payloadData);

        return $payloadData;
    }

    /**
     * @param array<string,mixed> $data
     */
    private function assertHasEmailDetailData(array $data): void
    {
        Assert::assertArrayHasKey('email', $data);

        $emailData = $data['email'];
        Assert::assertIsArray($emailData);
        Assert::assertArrayHasKey('customHtml', $emailData);
        Assert::assertArrayHasKey('plainText', $emailData);
    }

    /**
     * @param array<string,mixed> $data
     */
    private function assertNotHasEmailDetailData(array $data): void
    {
        Assert::assertArrayHasKey('email', $data);

        $emailData = $data['email'];
        Assert::assertIsArray($emailData);
        Assert::assertArrayNotHasKey('customHtml', $emailData);
        Assert::assertArrayNotHasKey('plainText', $emailData);
    }
}
