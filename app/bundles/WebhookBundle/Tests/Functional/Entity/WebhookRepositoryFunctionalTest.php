<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Functional\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\EmailEvents;
use Mautic\FormBundle\FormEvents;
use Mautic\LeadBundle\LeadEvents;
use Mautic\PageBundle\PageEvents;
use Mautic\SmsBundle\SmsEvents;
use Mautic\WebhookBundle\Entity\Event;
use Mautic\WebhookBundle\Entity\Webhook;
use Mautic\WebhookBundle\Entity\WebhookRepository;

final class WebhookRepositoryFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    private WebhookRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $repository = $this->em->getRepository(Webhook::class);
        $this->assertInstanceOf(WebhookRepository::class, $repository);
        $this->repository = $repository;

        $this->truncateTables('webhook_events', 'webhooks');
    }

    public function testItFiltersWebhooksByEventAlias(): void
    {
        $this->createDefaultWebhooks();

        $this->assertSame(['Company Webhook'], $this->getSearchResultNames('event:company'));
        $this->assertSame(['Contact Webhook'], $this->getSearchResultNames('event:lead'));
        $this->assertSame(['Contact Webhook'], $this->getSearchResultNames('event:contact'));
        $this->assertSame(['Email Webhook', 'Hybrid Webhook'], $this->getSearchResultNames('event:email'));
        $this->assertSame(['Form Webhook'], $this->getSearchResultNames('event:form'));
        $this->assertSame(['Hybrid Webhook', 'Page Webhook'], $this->getSearchResultNames('event:page'));
        $this->assertSame(['Text Message Webhook'], $this->getSearchResultNames('event:text-message'));
    }

    public function testItSupportsMultipleAliasesInSingleQuery(): void
    {
        $this->createDefaultWebhooks();

        $this->assertSame(['Contact Webhook', 'Form Webhook'], $this->getSearchResultNames('event:lead,form'));
    }

    public function testItSupportsMultipleEventTypesWithSpacesAsAndOperation(): void
    {
        $this->createDefaultWebhooks();

        // This should return empty because no webhook has BOTH email AND form events
        $this->assertSame([], $this->getSearchResultNames('event:email event:form'));

        // But this should work because Hybrid Webhook has both email AND page events
        $this->assertSame(['Hybrid Webhook'], $this->getSearchResultNames('event:email event:page'));
    }

    public function testItExcludesMatchingEventsWhenNegated(): void
    {
        $this->createDefaultWebhooks();

        $this->assertSame(['Company Webhook', 'Contact Webhook', 'Form Webhook', 'Page Webhook', 'Text Message Webhook'], $this->getSearchResultNames('!event:email'));
    }

    private function createDefaultWebhooks(): void
    {
        $this->buildWebhook('Company Webhook', [
            LeadEvents::COMPANY_POST_SAVE,
            LeadEvents::LEAD_COMPANY_CHANGE,
        ]);
        $this->buildWebhook('Contact Webhook', [
            LeadEvents::LEAD_POST_SAVE.'_new',
        ]);
        $this->buildWebhook('Email Webhook', [
            EmailEvents::EMAIL_ON_OPEN,
        ]);
        $this->buildWebhook('Form Webhook', [
            FormEvents::FORM_ON_SUBMIT,
        ]);
        $this->buildWebhook('Page Webhook', [
            PageEvents::PAGE_ON_HIT,
        ]);
        $this->buildWebhook('Text Message Webhook', [
            SmsEvents::SMS_ON_SEND,
        ]);
        $this->buildWebhook('Hybrid Webhook', [
            EmailEvents::EMAIL_ON_SEND,
            PageEvents::PAGE_ON_HIT,
        ]);

        $this->em->flush();
    }

    /**
     * @param string[] $eventTypes
     */
    private function buildWebhook(string $name, array $eventTypes): Webhook
    {
        $webhook = new Webhook();
        $webhook->setName($name);
        $webhook->setWebhookUrl('https://example.com/'.rawurlencode(strtolower(str_replace(' ', '-', $name))));
        $webhook->setSecret(hash('crc32b', $name));
        $webhook->setIsPublished(true);
        $webhook->setCreatedBy(1);

        foreach ($eventTypes as $eventType) {
            $event = new Event();
            $event->setEventType($eventType);
            $event->setWebhook($webhook);
            $this->em->persist($event);
            $webhook->addEvent($event);
        }

        $this->em->persist($webhook);

        return $webhook;
    }

    /**
     * @return string[]
     */
    private function getSearchResultNames(string $query): array
    {
        $results = $this->repository->getEntities([
            'filter'           => ['string' => $query],
            'ignore_paginator' => true,
        ]);

        return array_values(array_map(
            static fn (Webhook $webhook): string => $webhook->getName(),
            $results
        ));
    }
}
