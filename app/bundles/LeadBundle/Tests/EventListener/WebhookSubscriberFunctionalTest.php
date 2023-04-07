<?php

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookQueue;
use Mautic\WebhookBundle\Entity\WebhookQueueRepository;
use Mautic\WebhookBundle\Model\WebhookModel;
use PHPUnit\Framework\Assert;

class WebhookSubscriberFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->setUpSymfony(
            $this->configParams +
            [
                'queue_mode' => WebhookModel::COMMAND_PROCESS,
            ]
        );
        $this->truncateTables('leads', 'webhooks', 'webhook_queue', 'webhook_events');
    }

    public function testOnSegmentChange(): void
    {
        /** @var LeadRepository $contactRepository */
        $contactRepository = $this->em->getRepository(Lead::class);

        /** @var ListModel $segmentModel */
        $segmentModel = self::$container->get('mautic.lead.model.list');

        /** @var WebhookQueueRepository $webhookQueueRepository */
        $webhookQueueRepository = $this->em->getRepository(WebhookQueue::class);

        $webhook = $this->createWebhook();

        $segment = new LeadList();
        $segment->setName('Some segment');
        $segmentModel->saveEntity($segment);

        $contacts = [new Lead()];
        $contactRepository->saveEntities($contacts);

        Assert::assertSame(0, $webhookQueueRepository->getQueueCountByWebhookId($webhook->getId()));

        $segmentModel->addLead($contacts[0], $segment);

        Assert::assertSame(1, $webhookQueueRepository->getQueueCountByWebhookId($webhook->getId()));

        $payload = $this->em->getConnection()->createQueryBuilder()->select('payload')
            ->from(MAUTIC_TABLE_PREFIX.'webhook_queue', 'wq')
            ->where('wq'.'.webhook_id = :id')
            ->setParameter('id', $webhook->getId())
            ->execute()
            ->fetchColumn();

        $decodedPayload = json_decode($payload, true);
        Assert::assertEquals('added', $decodedPayload['action']);
    }

    private function createWebhook(): Webhook
    {
        $webhook = new Webhook();
        $event   = new Event();

        $event->setEventType('mautic.lead_list_change');
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
}
