<?php

declare(strict_types=1);

namespace Mautic\PageBundle\EventListener;

use Mautic\PageBundle\Event\PageHitEvent;
use Mautic\PageBundle\PageEvents;
use Mautic\WebhookBundle\Event\WebhookBuilderEvent;
use Mautic\WebhookBundle\Model\WebhookModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class WebhookSubscriber
{
    public function __construct(
        private WebhookModel $webhookModel,
    ) {
    }

    /**
     * Add event triggers and actions.
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: WebhookBuilderEvent::class)]
    public function onWebhookBuild(WebhookBuilderEvent $event): void
    {
        // add checkbox to the webhook form for new leads
        $pageHit = [
            'label'       => 'mautic.page.webhook.event.hit',
            'description' => 'mautic.page.webhook.event.hit_desc',
        ];

        // add it to the list
        $event->addEvent(PageEvents::PAGE_ON_HIT, $pageHit);
    }

    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: PageHitEvent::class)]
    public function onPageHit(PageHitEvent $event): void
    {
        $this->webhookModel->queueWebhooksByType(
            PageEvents::PAGE_ON_HIT,
            [
                'hit' => $event->getHit(),
            ],
            [
                'hitDetails',
                'emailDetails',
                'pageList',
                'leadList',
            ]
        );
    }
}
