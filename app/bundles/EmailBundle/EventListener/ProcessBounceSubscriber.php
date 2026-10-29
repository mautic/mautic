<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\EmailBundle\Event\MonitoredEmailEvent;
use Mautic\EmailBundle\Event\ParseEmailEvent;
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class ProcessBounceSubscriber
{
    public const string BUNDLE     = 'EmailBundle';
    public const string FOLDER_KEY = 'bounces';

    public function __construct(
        private Bounce $bouncer,
    ) {
    }

    #[AsEventListener(
        event: MonitoredEmailEvent::class,
        priority: 0
    )]
    public function onEmailConfig(MonitoredEmailEvent $event): void
    {
        $event->addFolder(self::BUNDLE, self::FOLDER_KEY, 'mautic.email.config.monitored_email.bounce_folder');
    }

    #[AsEventListener(
        event: ParseEmailEvent::class,
        priority: 0
    )]
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
