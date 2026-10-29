<?php

namespace Mautic\PageBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\PageBundle\Event\PageDisplayEvent;

final class TokenSubscriber
{
    #[AsEventListener(event: PageDisplayEvent::class, priority: 254)]
    public function decodeTokens(PageDisplayEvent $event): void
    {
        // Find and replace encoded tokens for trackable URL conversion
        $content = $event->getContent();
        $content = preg_replace('/(%7B)(.*?)(%7D)/i', '{$2}', $content);
        $event->setContent($content);
    }
}
