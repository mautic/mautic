<?php

namespace Mautic\EmailBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\CoreBundle\Helper\DateTime\DateTimeToken;
use Mautic\EmailBundle\Event\EmailDisplayEvent;
use Mautic\EmailBundle\Event\EmailOnBuildEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class DateTimeTokenSubscriber
{
    public function __construct(
        private TranslatorInterface $translator,
        private DateTimeToken $dateTokenHelper,
    ) {
    }

    #[AsEventListener(event: EmailOnBuildEvent::class)]
    public function onEmailBuild(EmailOnBuildEvent $event): void
    {
        $event->addToken('{today}', $this->translator->trans('mautic.core.token.group.other').': '.$this->translator->trans('mautic.email.token.today'));
    }

    #[AsEventListener(event: EmailDisplayEvent::class)]
    public function onEmailDisplay(EmailSendEvent $event): void
    {
        $this->onEmailGenerate($event);
    }

    #[AsEventListener(event: EmailSendEvent::class)]
    public function onEmailGenerate(EmailSendEvent $event): void
    {
        $content = $event->getSubject();
        $content .= $event->getContent();
        $content .= $event->getPlainText();
        $content .= implode(' ', $event->getTextHeaders());

        $leadArray       = $event->getLead();
        $contactTimezone = $event->isInternalSend() || !is_array($leadArray) ? null : ($leadArray['timezone'] ?? null);
        $tokenList       = $this->dateTokenHelper->getTokens($content, $contactTimezone);
        if (count($tokenList)) {
            $event->addTokens($tokenList);
            unset($tokenList);
        }
    }
}
