<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\Event\MonitoredEmailEvent;
use Mautic\EmailBundle\Event\ParseEmailEvent;
use Mautic\EmailBundle\MonitoredEmail\Processor\FeedbackLoop;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscribe;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class ProcessUnsubscribeSubscriber
{
    public const string BUNDLE     = 'EmailBundle';
    public const string FOLDER_KEY = 'unsubscribes';

    public function __construct(
        private Unsubscribe $unsubscriber,
        private FeedbackLoop $looper,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    #[AsEventListener(
        event: MonitoredEmailEvent::class,
    )]
    public function onEmailConfig(MonitoredEmailEvent $event): void
    {
        $event->addFolder(self::BUNDLE, self::FOLDER_KEY, 'mautic.email.config.monitored_email.unsubscribe_folder');
    }

    #[AsEventListener(
        event: ParseEmailEvent::class,
    )]
    public function onEmailParse(ParseEmailEvent $event): void
    {
        if ($event->isApplicable(self::BUNDLE, self::FOLDER_KEY)) {
            // Process the messages
            $messages = $event->getMessages();
            foreach ($messages as $message) {
                if (!$this->unsubscriber->process($message)) {
                    $this->looper->process($message);
                }
            }
        }
    }

    /**
     * Add an unsubscribe email to the List-Unsubscribe header if applicable.
     */
    #[AsEventListener(
        event: EmailSendEvent::class,
    )]
    public function onEmailSend(EmailSendEvent $event): void
    {
        if ($this->coreParametersHelper->get('disable_unsubscribe_link_header')) {
            return;
        }
        $helper = $event->getHelper();
        if ($helper && $unsubscribeEmail = $helper->generateUnsubscribeEmail()) {
            $headers          = $event->getTextHeaders();
            $existing         = $headers['List-Unsubscribe'] ?? '';
            $unsubscribeEmail = "<mailto:{$unsubscribeEmail}>";
            if ($existing) {
                if (!str_contains($existing, $unsubscribeEmail)) {
                    $updatedHeader = $existing.', '.$unsubscribeEmail;
                } else {
                    $updatedHeader = $existing;
                }
            } else {
                $updatedHeader = $unsubscribeEmail;
            }

            $event->addTextHeader('List-Unsubscribe', $updatedHeader);
            $event->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        }
    }
}
