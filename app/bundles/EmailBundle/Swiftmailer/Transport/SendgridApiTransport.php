<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use Mautic\EmailBundle\Swiftmailer\SendGrid\SendGridApiFacade;
use Swift_Events_EventListener;
use Swift_Mime_Message;

class SendgridApiTransport implements \Swift_Transport, TokenTransportInterface
{
    /**
     * @var SendGridApiFacade
     */
    private $sendGridApiFacade;

    /**
     * @var \Swift_Events_SimpleEventDispatcher
     */
    private $swiftEventDispatcher;

    /**
     * @var bool
     */
    private $started = false;

    public function __construct(SendGridApiFacade $sendGridApiFacade)
    {
        $this->sendGridApiFacade = $sendGridApiFacade;
    }

    /**
     * Test if this Transport mechanism has started.
     *
     * @return bool
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * Start this Transport mechanism.
     *
     * @throws \Swift_TransportException
     */
    public function start()
    {
        $this->started = true;
    }

    /**
     * Stop this Transport mechanism.
     */
    public function stop()
    {
    }

    /**
     * Send the given Message.
     *
     * Recipient/sender data will be retrieved from the Message API.
     * The return value is the number of recipients who were accepted for delivery.
     *
     * @param Swift_Mime_Message $message
     * @param string[]           $failedRecipients An array of failures by-reference
     *
     * @return int
     *
     * @throws \Swift_TransportException
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->sendGridApiFacade->send($message, $failedRecipients);

        return count($message->getTo());
    }

    /**
     * Register a plugin in the Transport.
     *
     * @param Swift_Events_EventListener $plugin
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        $this->getDispatcher()->bindEventListener($plugin);
    }

    /**
     * @return \Swift_Events_SimpleEventDispatcher
     */
    private function getDispatcher()
    {
        if ($this->swiftEventDispatcher === null) {
            $this->swiftEventDispatcher = new \Swift_Events_SimpleEventDispatcher();
        }

        return $this->swiftEventDispatcher;
    }

    /**
     * Return the max number of to addresses allowed per batch.  If there is no limit, return 0.
     *
     * @return int
     */
    public function getMaxBatchLimit()
    {
        return 1000;
    }

    /**
     * Get the count for the max number of recipients per batch.
     *
     * @param \Swift_Message $message
     * @param int            $toBeAdded Number of emails about to be added
     * @param string         $type      Type of emails being added (to, cc, bcc)
     *
     * @return int
     */
    public function getBatchRecipientCount(\Swift_Message $message, $toBeAdded = 1, $type = 'to')
    {
        return count($message->getTo());
    }

    /**
     * Function required to check that $this->message is instanceof MauticMessage, return $this->message->getMetadata() if it is and array() if not.
     *
     * @throws \Exception
     */
    public function getMetadata()
    {
        throw new \Exception('Not implemented');
    }
}
