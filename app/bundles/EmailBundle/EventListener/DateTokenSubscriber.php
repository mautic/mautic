<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\Helper\DateTokenHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DateTokenSubscriber implements EventSubscriberInterface
{
    private DateTokenHelper $dateTokenHelper;

    public function __construct(DateTokenHelper $dateTokenHelper)
    {
        $this->dateTokenHelper = $dateTokenHelper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_BUILD                     => ['onEmailBuild', 0],
            EmailEvents::EMAIL_ON_SEND                      => ['onEmailGenerate', 0],
            EmailEvents::EMAIL_ON_DISPLAY                   => ['onEmailDisplay', 0],
        ];
    }

    public function onEmailBuild(EmailBuilderEvent $event): void
    {
        $event->addTokens($this->dateTokenHelper->getTokens());
    }

    public function onEmailDisplay(EmailSendEvent $event): void
    {
        $this->onEmailGenerate($event);
    }

    public function onEmailGenerate(EmailSendEvent $event): void
    {
        $content = $event->getSubject();
        $content .= $event->getContent();
        $content .= $event->getPlainText();
        $content .= implode(' ', $event->getTextHeaders());

        $leadArray       = $event->getLead();
        $contactTimezone = $event->isInternalSend() || !is_array($leadArray) ? null : ($leadArray['timezone'] ?? null);
        $tokenList       = $this->dateTokenHelper->getReplacedTokens($content, $contactTimezone);
        if (count($tokenList)) {
            $event->addTokens($tokenList);
            unset($tokenList);
        }
    }
}
