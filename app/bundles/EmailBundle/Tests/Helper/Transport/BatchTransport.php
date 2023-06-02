<?php

namespace Mautic\EmailBundle\Tests\Helper\Transport;

use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Mautic\EmailBundle\Mailer\Transport\AbstractTokenArrayTransport;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;

class BatchTransport extends AbstractTokenArrayTransport
{
    /**
     * @var array<string, mixed>
     */
    private $transports = []; // @phpstan-ignore-line

    private $fromAddresses = [];

    private $fromNames = [];

    private $metadatas = [];

    private $validate = false;

    private $maxRecipients;

    private $numberToFail;

    /**
     * BatchTransport constructor.
     *
     * @param bool $validate
     */
    public function __construct($validate = false, $maxRecipients = 4, $numberToFail = 1)
    {
        $this->validate           = $validate;
        $this->maxRecipients      = $maxRecipients;
        $this->numberToFail       = (int) $numberToFail;
        $this->transports['main'] = $this;
    }

    public function __toString(): string
    {
        return 'batch://';
    }

    protected function doSend(SentMessage $message): void
    {
        // TODO: @escopecz if you have a better approach, please let me know
        $this->message         = $message->getOriginalMessage(); // @phpstan-ignore-line it will return either Email or MauticMessage
        $from                  = $this->message->getFrom(); // @phpstan-ignore-line we are sure this function exists
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
    public function getBatchRecipientCount(Email $message, $toBeAdded = 1, $type = 'to')
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
}
