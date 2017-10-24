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
use Mautic\EmailBundle\Event\ParseEmailEvent;
use Mautic\EmailBundle\MonitoredEmail\Processor\FeedBackLoop;
use Mautic\EmailBundle\MonitoredEmail\Processor\Unsubscribe;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProcessUnsubscribeSubscriber implements EventSubscriberInterface
{
    /**
     * @var Unsubscribe
     */
    protected $unsubscriber;

    /**
     * @var FeedBackLoop
     */
    protected $looper;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::EMAIL_PARSE => ['onEmailParse', 0],
        ];
    }

    /**
     * ProcessUnsubscribeSubscriber constructor.
     *
     * @param Unsubscribe  $unsubscriber
     * @param FeedBackLoop $looper
     */
    public function __construct(Unsubscribe $unsubscriber, FeedBackLoop $looper)
    {
        $this->unsubscriber = $unsubscriber;
        $this->looper       = $looper;
    }

    /**
     * @param ParseEmailEvent $event
     */
    public function onEmailParse(ParseEmailEvent $event)
    {
        if ($event->isApplicable('EmailBundle', 'unsubscribes')) {
            // Process the messages
            $messages = $event->getMessages();
            foreach ($messages as $message) {
                if (!$this->unsubscriber->setMessage($message)->process()) {
                    $this->looper->setMessage($message)->process();
                }
            }
        }
    }
}
