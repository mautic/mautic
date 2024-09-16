<?php

namespace Mautic\LeadBundle\EventListener;

use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ContactTracker $contactTracker,
        private TokenHelper $leadTokenHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PageEvents::PAGE_ON_DISPLAY => ['replaceContactFieldTokenOnPageDisplay', 0],
        ];
    }

    public function replaceContactFieldTokenOnPageDisplay(PageDisplayEvent $event): void
    {
        $content = $event->getContent();
        $lead    = $this->contactTracker->getContact();
        $event->setContent($this->leadTokenHelper->findLeadTokens($content, $lead?->convertToArray() ?? [], replace: true));
    }
}
