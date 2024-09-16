<?php

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
     * @param null $failedRecipients
     */
    public function send(\Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
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
     * @param int    $toBeAdded
     * @param string $type
     *
     * @return int
     */
    public function getBatchRecipientCount(\Swift_Message $message, $toBeAdded = 1, $type = 'to')
    {
        $to      = $message->getTo();
        $toCount = (is_array($to) || $to instanceof \Countable) ? count($to) : 0;

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

    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        // ignore
    }

    public function start()
    {
        // ignore
    }

    /**
     * @return bool
     */
    public function ping()
    {
        return true;
    }
}
