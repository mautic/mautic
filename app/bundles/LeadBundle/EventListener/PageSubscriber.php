<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Helper\TokenHelper as LeadTokenHelper;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PageSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PageEvents::PAGE_ON_DISPLAY => ['replaceContactFieldTokenOnPageDisplay', 0],
        ];
    }

    public function replaceContactFieldTokenOnPageDisplay(PageDisplayEvent $event): void
    {
        $content = $event->getContent();
        $lead    = $event->getLead();
        $event->setContent(LeadTokenHelper::findLeadTokens($content, $lead?->convertToArray() ?? [], replace: true));
    }
}
