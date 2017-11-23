<?php

/*
 * Created by PhpStorm.
 * User: alan
 * Date: 9/14/16
 * Time: 5:42 PM.
 */

namespace Mautic\EmailBundle\Tests\Helper\Transport;

use Mautic\EmailBundle\Swiftmailer\Transport\AbstractTokenArrayTransport;

class BatchTransport extends AbstractTokenArrayTransport implements \Swift_Transport
{
    private $fromAddresses = [];
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
     * @param \Swift_Mime_Message $message
     * @param null                $failedRecipients
     */
    public function send(\Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->message         = $message;
        $this->fromAddresses[] = key($message->getFrom());
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
}
