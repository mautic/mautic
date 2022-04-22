<?php

namespace Mautic\EmailBundle\Tests\Helper\Transport;

use Mautic\EmailBundle\Swiftmailer\Transport\AbstractTokenArrayTransport;

class BatchTransport extends AbstractTokenArrayTransport implements \Swift_Transport
{
    private $fromAddresses = [];
    private $fromNames     = [];
    private $metadatas     = [];
    private $validate      = false;
    private $maxRecipients;
    private $numberToFail;

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
        $from                  = $message->getFrom();
        $fromEmail             = key($from);
        $this->fromAddresses[] = $fromEmail;
        $this->fromNames[]     = $from[$fromEmail];
        $this->metadatas[]     = $this->getMetadata();

        $messageArray = $this->messageToArray();

        if ($this->validate && $this->numberToFail) {
            --$this->numberToFail;

            if (empty($messageArray['subject'])) {
                $this->throwException('Subject empty');
            }

            if (empty($messageArray['recipients']['to'])) {
                $this->throwException('To empty');
            }
        }

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
     * return array.
     */
    public function getFromNames()
    {
        return $this->fromNames;
    }

    /**
     * @return array
     */
    public function getMetadatas()
    {
        return $this->metadatas;
    }

    /**
     * @return bool
     */
    public function ping()
    {
        return true;
    }
}
