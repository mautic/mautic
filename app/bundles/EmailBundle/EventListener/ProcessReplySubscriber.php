<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\MonitoredEmailEvent;
use Mautic\EmailBundle\Event\ParseEmailEvent;
use Mautic\EmailBundle\MonitoredEmail\Processor\Reply;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProcessReplySubscriber implements EventSubscriberInterface
{
    const BUNDLE     = 'EmailBundle';
    const FOLDER_KEY = 'replies';

    /**
     * @var Reply
     */
    protected $replier;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::MONITORED_EMAIL_CONFIG => ['onEmailConfig', 0],
            EmailEvents::EMAIL_PRE_FETCH        => ['onEmailPreFetch', 0],
            EmailEvents::EMAIL_PARSE            => ['onEmailParse', 0],
        ];
    }

    /**
     * ProcessReplySubscriber constructor.
     *
     * @param Reply $replier
     */
    public function __construct(Reply $replier)
    {
        $this->replier = $replier;
    }

    /**
     * @param MonitoredEmailEvent $event
     */
    public function onEmailConfig(MonitoredEmailEvent $event)
    {
        $event->addFolder(self::BUNDLE, self::FOLDER_KEY, 'mautic.email.config.monitored_email.reply_folder');
    }

    /**
     * @param ParseEmailEvent $event
     */
    public function onEmailPreFetch(ParseEmailEvent $event)
    {
        $event->setCriteriaRequest(self::BUNDLE, self::FOLDER_KEY, '');
    }

    /**
     * @param ParseEmailEvent $event
     */
    public function onEmailParse(ParseEmailEvent $event)
    {
        if ($event->isApplicable(self::BUNDLE, self::FOLDER_KEY)) {
            // Process the messages
            $messages = $event->getMessages();
            foreach ($messages as $message) {
                $this->replier->process($message);
            }
        }
    }
}
