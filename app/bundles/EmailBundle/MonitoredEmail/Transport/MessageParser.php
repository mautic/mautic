<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\MonitoredEmail\Transport;

use Mautic\EmailBundle\Exception\MailboxException;
use Mautic\EmailBundle\MonitoredEmail\Attachment;
use Mautic\EmailBundle\MonitoredEmail\Message;
use ZBateson\MailMimeParser\Header\AddressHeader;
use ZBateson\MailMimeParser\Message as MimeMessage;
use ZBateson\MailMimeParser\Message\IMessagePart;
use ZBateson\MailMimeParser\Message\IMimePart;
use ZBateson\MailMimeParser\Message\IMultiPart;

/** @internal Converts library MIME data to the existing monitored-email message. */
final class MessageParser
{
    public function parse(string $raw, int $id, bool $attachments, ?string $directory): Message
    {
        $mime = MimeMessage::from($raw, false);
        $mail = new Message();
        $mail->id = $id;
        $mail->subject = $mime->getSubject();
        $mail->date = date('Y-m-d H:i:s', strtotime($mime->getHeaderValue('date', 'now')) ?: time());
        $from = self::addresses($mime->getHeader('from')?->getRawValue() ?? '');
        $mail->fromAddress = strtolower((string) array_key_first($from));
        $mail->fromName = reset($from) ?: null;
        foreach (['to' => 'to', 'cc' => 'cc', 'reply-to' => 'replyTo'] as $header => $property) {
            foreach (self::addresses($mime->getHeader($header)?->getRawValue() ?? '') as $address => $name) {
                $mail->{$property}[strtolower($address)] = $name;
            }
        }
        $to = [];
        foreach ($mail->to as $address => $name) {
            $to[] = $name ? $name.' <'.$address.'>' : $address;
        }
        $mail->toString = implode(', ', $to);
        // These identifiers must retain their angle brackets for reply matching.
        foreach (['in-reply-to' => 'inReplyTo', 'return-path' => 'returnPath'] as $header => $property) {
            $value = $mime->getHeader($header)?->getRawValue();
            if (null !== $value) {
                $mail->{$property} = $value;
            }
        }
        if (null !== $references = $mime->getHeader('references')?->getRawValue()) {
            $mail->references = explode("\n", $references);
        }
        foreach ($mime->getAllHeaders() as $header) {
            $name = strtolower($header->getName());
            if (str_starts_with($name, 'x-')) {
                $mail->xHeaders[$name] = $header->getValue();
            }
        }
        $this->readPart($mail, $mime, $attachments, $directory, true);

        return $mail;
    }

    /** @return array<string, string|null> */
    public static function addresses(string $value): array
    {
        $header = MimeMessage::from('To: '.$value."\r\n\r\n", false)->getHeader('to');
        $result = [];
        if ($header instanceof AddressHeader) {
            foreach ($header->getAddresses() as $address) {
                $email = $address->getEmail();
                if (str_contains($email, '@') && !str_contains($email, '.SYNTAX-ERROR.')) {
                    $result[$email] = $address->getName() ?: null;
                }
            }
        }

        return $result;
    }

    private function readPart(Message $mail, IMessagePart $part, bool $attachments, ?string $directory, bool $root = false): void
    {
        $type = strtolower($part->getContentType() ?? '');
        $name = $part->getFilename();
        $cid  = $part->getContentId();
        if ((!$root || !str_starts_with($type, 'text/')) && ($name || $cid)) {
            if ($attachments) {
                $attachment = new Attachment();
                $attachment->id = $cid ?: bin2hex(random_bytes(16));
                $attachment->name = $name ?: $attachment->id.'.'.substr($type, strpos($type, '/') + 1);
                if (null !== $directory) {
                    // Server-controlled names and Content-IDs must never become paths.
                    $safe = preg_replace('/[^\pL\pN_.-]/u', '_', $attachment->name);
                    $attachment->filePath = $directory.'/'.$mail->id.'_'.bin2hex(random_bytes(16)).'_'.$safe;
                    try {
                        $part->saveContent($attachment->filePath);
                    } catch (\Exception $e) {
                        throw new MailboxException('Unable to save an IMAP attachment.', 0, $e);
                    }
                }
                $mail->addAttachment($attachment);
            }

            return;
        }
        if ('message/delivery-status' === $type) {
            $mail->dsnReport = $part->getContent() ?? '';
        } elseif ('message/feedback-report' === $type) {
            $mail->fblReport = $part->getContent() ?? '';
        } elseif ('text/plain' === $type) {
            $mail->textPlain .= $part->getContent();
        } elseif (str_starts_with($type, 'text/')) {
            $mail->textHtml .= $part->getContent();
        } elseif ('multipart/report' === $type && $part instanceof IMimePart) {
            $report = $part->getHeaderParameter('content-type', 'report-type');
            if ('delivery-status' === $report) {
                $mail->dsnMessage = trim($part->getContent() ?? '');
            } elseif ('feedback-report' === $report) {
                $mail->fblMessage = trim($part->getContent() ?? '');
            }
        } elseif ('message/rfc822' === $type) {
            $mail->textPlain .= trim($part->getContent() ?? '');
        }
        if ($part instanceof IMultiPart) {
            foreach ($part->getChildParts() as $child) {
                $this->readPart($mail, $child, $attachments, $directory);
            }
        }
    }
}
