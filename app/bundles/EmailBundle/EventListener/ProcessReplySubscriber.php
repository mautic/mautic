<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\CacheBundle\Cache\CacheProviderInterface;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\MonitoredEmailEvent;
use Mautic\EmailBundle\Event\ParseEmailEvent;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\MonitoredEmail\Processor\Reply;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class ProcessReplySubscriber implements EventSubscriberInterface
{
    public const string BUNDLE     = 'EmailBundle';

    public const string FOLDER_KEY = 'replies';

    public const string CACHE_KEY  = self::BUNDLE.'_'.self::FOLDER_KEY;

    public static function getSubscribedEvents(): array
    {
        return [
            EmailEvents::MONITORED_EMAIL_CONFIG => ['onEmailConfig', 0],
            EmailEvents::EMAIL_PRE_FETCH        => ['onEmailPreFetch', 0],
            EmailEvents::EMAIL_PARSE            => ['onEmailParse', 1],
        ];
    }

    public function __construct(
        private Reply $replier,
        private CacheProviderInterface $cache,
    ) {
    }

    public function onEmailConfig(MonitoredEmailEvent $event): void
    {
        $event->addFolder(self::BUNDLE, self::FOLDER_KEY, 'mautic.email.config.monitored_email.reply_folder');
    }

    public function onEmailPreFetch(ParseEmailEvent $event): void
    {
        if (!$lastFetchedUID = $this->cache->getSimpleCache()->get(self::CACHE_KEY)) {
            return;
        }

        $startingUID = $lastFetchedUID + 1;

        // Using * will return the last UID even if the starting UID doesn't exist so let's just use a highball number
        $endingUID = $startingUID + 1_000_000_000;

        $event->setCriteriaRequest(self::BUNDLE, self::FOLDER_KEY, Mailbox::CRITERIA_UID." {$startingUID}:{$endingUID}");
    }

    public function onEmailParse(ParseEmailEvent $event): void
    {
        // Process the messages
        if ($event->isApplicable(self::BUNDLE, self::FOLDER_KEY) && $messages = $event->getMessages()) {
            foreach ($messages as $message) {
                $this->replier->process($message);
            }
            // Store the last UID
            $this->cache->getSimpleCache()->set(self::CACHE_KEY, $message->id);
        }
    }
}
