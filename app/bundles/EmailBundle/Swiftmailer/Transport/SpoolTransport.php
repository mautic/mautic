<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use Mautic\EmailBundle\Swiftmailer\Spool\DelegatingSpool;

class SpoolTransport extends \Swift_Transport_SpoolTransport
{
    /**
     * @var \Swift_Events_EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var DelegatingSpool
     */
    private $spool;

    /**
     * SpoolTransport constructor.
     */
    public function __construct(\Swift_Events_EventDispatcher $eventDispatcher, DelegatingSpool $delegatingSpool)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->spool           = $delegatingSpool;

        parent::__construct($eventDispatcher, $delegatingSpool);
    }

    /**
     * Sends or queues the given message.
     *
     * @param string[] $failedRecipients An array of failures by-reference
     *
     * @return int The number of sent e-mails
     *
     * @throws \Swift_IoException
     */
    public function send(\Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        if ($evt = $this->eventDispatcher->createSendEvent($this, $message)) {
            $this->eventDispatcher->dispatchEvent($evt, 'beforeSendPerformed');
            if ($evt->bubbleCancelled()) {
                return 0;
            }
        }

        $count = $this->spool->delegateMessage($message, $failedRecipients);

        if ($evt) {
            $successResult = $this->spool->wasMessageSpooled() ? \Swift_Events_SendEvent::RESULT_SPOOLED : \Swift_Events_SendEvent::RESULT_SUCCESS;
            $evt->setResult($count ? $successResult : \Swift_Events_SendEvent::RESULT_FAILED);
            $this->eventDispatcher->dispatchEvent($evt, 'sendPerformed');
        }

        return $count;
    }
}
