<?php

namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\Helper\DateTokenHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DateTokenSubscriber implements EventSubscriberInterface
{
    private ContactTracker $contactTracker;

    private DateTokenHelper $dateTokenHelper;

    private CorePermissions $security;

    public function __construct(DateTokenHelper $dateTokenHelper, CorePermissions $security, ContactTracker $contactTracker)
    {
        $this->dateTokenHelper = $dateTokenHelper;
        $this->security        = $security;
        $this->contactTracker  = $contactTracker;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PageEvents::PAGE_ON_BUILD                     => ['onPageBuild', 0],
            PageEvents::PAGE_ON_DISPLAY                   => ['onPageDisplay', 0],
        ];
    }

    public function onPageBuild(PageBuilderEvent $event): void
    {
        $event->addTokens($this->dateTokenHelper->getTokens());
    }

    public function onPageDisplay(PageDisplayEvent $event): void
    {
        $content   = $event->getContent();
        $contact   = $this->security->isAnonymous() ? $this->contactTracker->getContact() : null;

        $tokenList = $this->dateTokenHelper->getReplacedTokens($content, $contact ? $contact->getTimezone() : null);
        $event->setContent(str_replace(array_keys($tokenList), $tokenList, $content));
    }
}
