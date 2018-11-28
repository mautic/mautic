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

use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\MonitoredEmailEvent;
use Mautic\EmailBundle\Event\ParseEmailEvent;
use Mautic\EmailBundle\MonitoredEmail\Mailbox;
use Mautic\EmailBundle\MonitoredEmail\Processor\Reply;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProcessReplySubscriber implements EventSubscriberInterface
{
    const BUNDLE     = 'EmailBundle';
    const FOLDER_KEY = 'replies';
    const CACHE_KEY  = self::BUNDLE.'_'.self::FOLDER_KEY;

    /**
     * @var Reply
     */
    private $replier;

    /**
     * @var CacheStorageHelper
     */
    private $cache;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::MONITORED_EMAIL_CONFIG => ['onEmailConfig', 0],
            EmailEvents::EMAIL_PRE_FETCH        => ['onEmailPreFetch', 0],
            EmailEvents::EMAIL_PARSE            => ['onEmailParse', 1],
        ];
    }

    /**
     * ProcessReplySubscriber constructor.
     *
     * @param Reply $replier
     */
    public function __construct(Reply $replier, CacheStorageHelper $cache)
    {
        $this->replier = $replier;
        $this->cache   = $cache;
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
        if (!$lastFetchedUID = $this->cache->get(self::CACHE_KEY)) {
            return;
        }

        $startingUID = $lastFetchedUID + 1;

        // Using * will return the last UID even if the starting UID doesn't exist so let's just use a highball number
        $endingUID = $startingUID + 1000000000;

        $event->setCriteriaRequest(self::BUNDLE, self::FOLDER_KEY, Mailbox::CRITERIA_UID." $startingUID:$endingUID");
    }

    /**
     * @param ParseEmailEvent $event
     */
    public function onEmailParse(ParseEmailEvent $event)
    {
        if ($event->isApplicable(self::BUNDLE, self::FOLDER_KEY)) {
            // Process the messages
            if ($messages = $event->getMessages()) {
                foreach ($messages as $message) {
                    $this->replier->process($message);
                }

                // Store the last UID
                $this->cache->set(self::CACHE_KEY, $message->id);
            }
        }
    }
}
