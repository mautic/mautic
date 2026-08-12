<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\EventSubscriber;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use MauticPlugin\GrapesJsBuilderBundle\Helper\MjmlContentHelper;
use MauticPlugin\GrapesJsBuilderBundle\Integration\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EmailPreviewSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Config $config,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::EMAIL_ON_DISPLAY => ['convertPublicMjmlPreview', -10000],
        ];
    }

    public function convertPublicMjmlPreview(EmailSendEvent $event): void
    {
        if (!$this->config->isPublished()) {
            return;
        }

        $source = $event->getSource();

        if (!is_array($source) || empty($source['publicPreview'])) {
            return;
        }

        $content = $event->getContent(true);

        if (!MjmlContentHelper::isMjml($content)) {
            return;
        }

        $html = MjmlContentHelper::toHtml($content);

        if (null !== $html) {
            $event->setContent($html);
        }
    }
}
