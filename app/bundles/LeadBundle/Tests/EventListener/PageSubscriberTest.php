<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Tests\CommonMocks;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\EventListener\PageSubscriber;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;

class PageSubscriberTest extends CommonMocks
{
    public function testSubscribedEvents(): void
    {
        $this->assertSame(
            [
                PageEvents::PAGE_ON_DISPLAY => ['replaceContactFieldTokenOnPageDisplay', 0],
            ],
            PageSubscriber::getSubscribedEvents()
        );
    }

    public function replaceContactFieldTokenOnPageDisplay(): void
    {
        $page = new Page();
        $page->setCustomHtml('<html><body>{contactfield=email}{contactfield=name}</body></html>');

        $lead = (new Lead())
            ->setEmail('john@doe.com');

        $event = new PageDisplayEvent($page->getCustomHtml(), $page, []);
        $event->setLead($lead);

        $pageSubscriber = new PageSubscriber();
        $pageSubscriber->replaceContactFieldTokenOnPageDisplay($event);

        $this->assertSame('<html><body>john@doe.com</body></html>', $event->getContent(), 'Token should get replaced by lead data or by an empty string for missing data.');
    }

    public function replaceContactFieldTokenWithEmptyStringForMissingLeadOnPageDisplay(): void
    {
        $page = new Page();
        $page->setCustomHtml('<html><body>{contactfield=email}</body></html>');

        $event = new PageDisplayEvent($page->getCustomHtml(), $page, []);

        $pageSubscriber = new PageSubscriber();
        $pageSubscriber->replaceContactFieldTokenOnPageDisplay($event);

        $this->assertSame('<html><body>john@doe.com</body></html>', $event->getContent(), 'Token should get replaced by an empty string.');
    }
}
