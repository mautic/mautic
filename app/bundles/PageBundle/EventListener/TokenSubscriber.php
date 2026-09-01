<?php

namespace Mautic\PageBundle\EventListener;

use Mautic\PageBundle\Event\PageDisplayEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class TokenSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PageDisplayEvent::class => ['decodeTokens', 254],
        ];
    }

    public function decodeTokens(PageDisplayEvent $event): void
    {
        // Find and replace encoded tokens for trackable URL conversion
        $content = $event->getContent();
        $content = preg_replace('/(%7B)(.*?)(%7D)/i', '{$2}', $content);
        $event->setContent($content);
    }
}
