<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;

/**
 * Class AbstractBatchTransport.
 */
abstract class AbstractTokenSmtpTransport extends \Swift_SmtpTransport implements TokenTransportInterface
{
    /**
     * @var \Swift_Mime_Message
     */
    protected $message;

    /**
     * Do whatever is necessary to $this->message in order to deliver a batched payload. i.e. add custom headers, etc.
     */
    abstract protected function prepareMessage();

    /**
     * @param \Swift_Mime_Message $message
     * @param null                $failedRecipients
     *
     * @return int
     *
     * @throws \Exception
     */
    public function send(\Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->message = $message;

        $this->prepareMessage();

        return parent::send($message, $failedRecipients);
    }

    /**
     * Get the metadata from a MauticMessage.
     *
     * @return array
     */
    public function getMetadata()
    {
        return ($this->message instanceof MauticMessage) ? $this->message->getMetadata() : [];
    }

    /**
     * Get attachments from a MauticMessage.
     *
     * @return array
     */
    public function getAttachments()
    {
        return ($this->message instanceof MauticMessage) ? $this->message->getAttachments() : [];
    }
}
