<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Helper;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\SmsBundle\Entity\Stat;
use Mautic\SmsBundle\Event\ReplyEvent;
use Mautic\SmsBundle\SmsEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class ReplyHelper.
 */
class ReplyHelper
{
    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * ReplyHelper constructor.
     *
     * @param EventDispatcher $eventDispatcher
     */
    public function __construct(EventDispatcher $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $pattern
     * @param string $replyBody
     *
     * @return bool
     */
    public static function matches($pattern, $replyBody)
    {
        return fnmatch($pattern, $replyBody);
    }

    /**
     * @param Lead      $contact
     * @param string    $message
     * @param Stat|null $stat
     */
    public function dispatchReplyEvent(Lead $contact, $message, Stat $stat = null)
    {
        $replyEvent = new ReplyEvent($contact, $message, $stat);

        $this->eventDispatcher->dispatch(SmsEvents::ON_REPLY, $replyEvent);
    }
}
