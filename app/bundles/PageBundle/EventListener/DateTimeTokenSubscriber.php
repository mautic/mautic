<?php

namespace Mautic\PageBundle\EventListener;

use Mautic\CoreBundle\Helper\DateTime\DateTimeToken;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Event\PageBuilderEvent;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DateTimeTokenSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private DateTimeToken $dateTokenHelper,
        private CorePermissions $security,
        private ContactTracker $contactTracker
    ) {
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
        $event->addToken('{today}', $this->translator->trans('mautic.email.token.today'));
    }

    public function onPageDisplay(PageDisplayEvent $event): void
    {
        $content   = $event->getContent();
        $contact   = $this->security->isAnonymous() ? $this->contactTracker->getContact() : null;

        $tokenList = $this->dateTokenHelper->getTokens($content, $contact ? $contact->getTimezone() : null);
        $event->setContent(str_replace(array_keys($tokenList), $tokenList, $content));
    }
}
