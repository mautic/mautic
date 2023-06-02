<?php

namespace Mautic\EmailBundle\Mailer\Transport;

use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Helper\PlainTextMessageHelper;
use Mautic\EmailBundle\Mailer\Message\MauticMessage;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

/**
 * The goal of this class is to provide a common interface for transports that support tokenized sending.
 * there are abstract functions that are inherited from the AbstractTransport class that must be implemented.
 */
abstract class AbstractTokenArrayTransport extends AbstractTransport implements TokenTransportInterface
{
    /**
     * @var MauticMessage
     */
    protected $message;

    /**
     * @var array<string>
     */
    protected $standardHeaderKeys = [
        'MIME-Version',
        'received',
        'dkim-signature',
        'Content-Type',
        'Content-Transfer-Encoding',
        'To',
        'From',
        'Subject',
        'Reply-To',
        'CC',
        'BCC',
    ];

    abstract protected function doSend(SentMessage $message): void;

    /**
     * Get the metadata from a MauticMessage.
     *
     * @return array<string, mixed>
     */
    public function getMetadata()
    {
        return $this->message->getMetadata();
    }

    /**
     * Replace the tokens in the message with their real values.
     *
     * @param array<string> $search  If the mailer requires tokens in another format than Mautic's, pass array of Mautic tokens to replace
     * @param array<string> $replace If the mailer requires tokens in another format than Mautic's, pass array of replacement tokens
     */
    protected function replaceTokens($search = [], $replace = []): void
    {
        if (!empty($search)) {
            MailHelper::searchReplaceTokens($search, $replace, $this->message);
        }
    }

    /**
     * Converts Mime\Email into associative array.
     * This function is kept for backward compatibility.
     *
     * @param array<string> $search            If the mailer requires tokens in another format than Mautic's, pass array of Mautic tokens to replace
     * @param array<string> $replace           If the mailer requires tokens in another format than Mautic's, pass array of replacement tokens
     * @param bool|false    $binaryAttachments True to convert file attachments to binary
     *
     * @return array<string, mixed>
     */
    protected function messageToArray($search = [], $replace = [], $binaryAttachments = false)
    {
        if (!empty($search)) {
            MailHelper::searchReplaceTokens($search, $replace, $this->message);
        }

        $from = $this->message->getFrom();
        foreach ($from as $address) {
            $fromEmail = $address->getEncodedAddress();
            $fromName  = $address->getEncodedName();
        }

        $message = [
            'html'    => $this->message->getTextBody(),
            'text'    => PlainTextMessageHelper::getPlainTextFromMessage($this->message),
            'subject' => $this->message->getSubject(),
            'from'    => [
                'name'  => $fromName,
                'email' => $fromEmail,
            ],
        ];

        $to = $this->message->getTo();
        foreach ($to as $address) {
            $message['recipients']['to'][$address->getEncodedAddress()] = [
                'email' => $address->getEncodedAddress(),
                'name'  => $address->getEncodedName(),
            ];
        }

        $cc = $this->message->getCc();
        if (!empty($cc)) {
            foreach ($cc as $address) {
                $message['recipients']['cc'][$address->getEncodedAddress()] = [
                    'email' => $address->getEncodedAddress(),
                    'name'  => $address->getEncodedName(),
                ];
            }
        }

        $bcc = $this->message->getBcc();
        if (!empty($bcc)) {
            foreach ($bcc as $address) {
                $message['recipients']['bcc'][$address->getEncodedAddress()] = [
                    'email' => $address->getEncodedAddress(),
                    'name'  => $address->getEncodedName(),
                ];
            }
        }

        $replyTo = $this->message->getReplyTo();
        if (!empty($replyTo)) {
            foreach ($replyTo as $address) {
                $message['replyTo'] = [
                    'email' => $address->getEncodedAddress(),
                    'name'  => $address->getEncodedName(),
                ];
            }
        }

        $returnPath = $this->message->getReturnPath();
        if (!empty($returnPath)) {
            $message['returnPath'] = $returnPath;
        }

        $tags     = [];
        $metaData = [];

        foreach ($this->message->getHeaders()->all() as $name => $header) {
            if (\in_array($name, $this->standardHeaderKeys, true)) {
                continue;
            }

            if ($header instanceof TagHeader) {
                $tags[] = mb_substr($header->getValue(), 0, 255);
            } elseif ($header instanceof MetadataHeader) {
                $metaData[$header->getKey()] = $header->getValue();
            } else {
                $payload['headers'][$header->getName()] = $header->getBodyAsString();
            }
        }
        $message['tags']     = $tags;
        $message['metadata'] = $metaData;

        $attachments = [];
        foreach ($this->message->getAttachments() as $attachment) {
            $headers       = $attachment->getPreparedHeaders();
            $filename      = $headers->getHeaderParameter('Content-Disposition', 'filename');
            $disposition   = $headers->getHeaderBody('Content-Disposition');
            $attachments[] = [
                'type'        => $headers->get('Content-Type')->getBody(),
                'name'        => $filename,
                'content'     => str_replace("\r\n", '', $attachment->bodyToString()),
                'disposition' => $disposition,
            ];
        }

        $message['attachments'] = $attachments;

        return $message;
    }

    /**
     * @param \Exception|string $exception
     *
     * @throws \Exception
     */
    protected function throwException($exception): void
    {
        if (!$exception instanceof \Exception) {
            $exception = new TransportException($exception);
        }

        throw $exception;
    }
}
