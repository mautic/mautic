<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Helper\Transport;

use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use Mautic\EmailBundle\Swiftmailer\Transport\InterfaceTokenTransport;
use Swift_Events_EventListener;

class BcInterfaceTokenTransport implements InterfaceTokenTransport, \Swift_Transport
{
    private $fromAddresses = [];
    private $metadatas     = [];
    private $validate      = false;
    private $maxRecipients;
    private $numberToFail;
    private $message;

    /**
     * BatchTransport constructor.
     *
     * @param bool $validate
     */
    public function __construct($validate = false, $maxRecipients = 4, $numberToFail = 1)
    {
        $this->validate      = $validate;
        $this->maxRecipients = $maxRecipients;
        $this->numberToFail  = (int) $numberToFail;
    }

    /**
     * @param \Swift_Mime_Message $message
     * @param null                $failedRecipients
     */
    public function send(\Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->message         = $message;
        $this->fromAddresses[] = key($message->getFrom());
        $this->metadatas[]     = $this->getMetadata();

        return true;
    }

    /**
     * @return int
     */
    public function getMaxBatchLimit()
    {
        return $this->maxRecipients;
    }

    /**
     * @param \Swift_Message $message
     * @param int            $toBeAdded
     * @param string         $type
     *
     * @return int
     */
    public function getBatchRecipientCount(\Swift_Message $message, $toBeAdded = 1, $type = 'to')
    {
        $toCount = count($message->getTo());

        return ('to' === $type) ? $toCount + $toBeAdded : $toCount;
    }

    /**
     * @return array
     */
    public function getFromAddresses()
    {
        return $this->fromAddresses;
    }

    /**
     * @return array
     */
    public function getMetadatas()
    {
        return $this->metadatas;
    }

    public function getMetadata()
    {
        return ($this->message instanceof MauticMessage) ? $this->message->getMetadata() : [];
    }

    /**
     * @return bool
     */
    public function isStarted()
    {
        return true;
    }

    public function stop()
    {
        // ignore
    }

    /**
     * @param Swift_Events_EventListener $plugin
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        // ignore
    }

    public function start()
    {
        // ignore
    }
}
