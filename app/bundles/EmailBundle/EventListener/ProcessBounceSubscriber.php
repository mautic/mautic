<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\MonitoredEmailEvent;
use Mautic\EmailBundle\Event\ParseEmailEvent;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class ProcessBounceSubscriber implements EventSubscriberInterface
{
    public const string BUNDLE     = 'EmailBundle';

    public const string FOLDER_KEY = 'bounces';

    public static function getSubscribedEvents(): array
    {
        return [
            MonitoredEmailEvent::class => ['onEmailConfig', 0],
            EmailEvents::EMAIL_PARSE            => ['onEmailParse', 0],
        ];
    }

    public function __construct(
        private Bounce $bouncer,
    ) {
    }

    public function onEmailConfig(MonitoredEmailEvent $event): void
    {
        $event->addFolder(self::BUNDLE, self::FOLDER_KEY, 'mautic.email.config.monitored_email.bounce_folder');
    }

    public function onEmailParse(ParseEmailEvent $event): void
    {
        if ($event->isApplicable(self::BUNDLE, self::FOLDER_KEY)) {
            // Process the messages
            $messages = $event->getMessages();
            foreach ($messages as $message) {
                $this->bouncer->process($message);
            }
        }
    }
}
