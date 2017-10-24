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
use Mautic\EmailBundle\MonitoredEmail\Processor\Bounce;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProcessBounceSubscriber implements EventSubscriberInterface
{
    const BUNDLE     = 'EmailBundle';
    const FOLDER_KEY = 'bounces';

    /**
     * @var Bounce
     */
    protected $bouncer;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::MONITORED_EMAIL_CONFIG => ['onEmailConfig', 0],
            EmailEvents::EMAIL_PARSE            => ['onEmailParse', 0],
        ];
    }

    /**
     * EmailBounceSubscriber constructor.
     *
     * @param Bounce $bouncer
     */
    public function __construct(Bounce $bouncer)
    {
        $this->bouncer = $bouncer;
    }

    /**
     * @param MonitoredEmailEvent $event
     */
    public function onEmailConfig(MonitoredEmailEvent $event)
    {
        $event->addFolder(self::BUNDLE, self::FOLDER_KEY, 'mautic.email.config.monitored_email.bounce_folder');
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
                $this->bouncer->process($message);
            }
        }
    }
}
