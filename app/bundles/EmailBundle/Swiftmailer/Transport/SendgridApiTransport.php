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

use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use SendGrid\Attachment;
use SendGrid\BccSettings;
use SendGrid\Content;
use SendGrid\Email;
use SendGrid\Mail;
use SendGrid\MailSettings;
use SendGrid\Personalization;
use SendGrid\ReplyTo;
use Swift_Events_EventListener;
use Swift_Mime_Message;
use Symfony\Component\Translation\TranslatorInterface;

class SendgridApiTransport implements \Swift_Transport, TokenTransportInterface
{
    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var \Swift_Events_SimpleEventDispatcher
     */
    private $swiftEventDispatcher;

    /**
     * @var bool
     */
    private $started = false;

    public function __construct($apiKey, TranslatorInterface $translator)
    {
        $this->apiKey     = $apiKey;
        $this->translator = $translator;
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
        if (empty($this->apiKey)) {
            $message = $this->translator->trans('mautic.email.api_key_required', [], 'validators');
            throw new \Swift_TransportException($message);
        }

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
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $from        = new Email(current($message->getFrom()), key($message->getFrom()));
        $subject     = $message->getSubject();
        $contentHtml = new Content('text/html', $message->getBody());
        $contentText = new Content('text/plain', MailHelper::getPlainTextFromMessage($message));

        // Sendgrid class requires to pass an TO email even if we do not have any general one
        // Pass a dummy email and clear it in the next 2 lines
        $to                    = 'dummy-email-to-be-deleted@example.com';
        $mail                  = new Mail($from, $subject, $to, $contentText);
        $mail->personalization = [];

        $mail->addContent($contentHtml);

        $mail_settings = new MailSettings();

        $metadata = ($message instanceof MauticMessage) ? $message->getMetadata() : [];
        foreach ($message->getTo() as $recipientEmail => $recipientName) {
            if (empty($metadata[$recipientEmail])) {
                //Recipient is not in metadata = we do not have tokens for this emil. Not sure if this can happen?
                echo 'NO METADATA for '.$recipientEmail;
                continue;
            }
            $personalization = new Personalization();
            $to              = new Email($recipientName, $recipientEmail);
            $personalization->addTo($to);

            foreach ($metadata[$recipientEmail]['tokens'] as $token => $value) {
                $personalization->addSubstitution($token, $value);
            }

            $mail->addPersonalization($personalization);
            unset($metadata[$recipientEmail]);
        }

        if ($message->getReplyTo()) {
            $replyTo = new ReplyTo(key($message->getReplyTo()));
            $mail->setReplyTo($replyTo);
        }
        if ($message->getCc()) {
            $bcc_settings = new BccSettings();
            $bcc_settings->setEnable(true);
            $bcc_settings->setEmail(key($message->getCc()));
            $mail_settings->setBccSettings($bcc_settings);
        }
        if ($message->getAttachments()) {
            foreach ($message->getAttachments() as $emailAttachment) {
                $fileContent = @file_get_contents($emailAttachment['filePath']);
                if ($fileContent === false) {
                    continue;
                }
                $base64 = base64_encode($fileContent);

                $attachment = new Attachment();
                $attachment->setContent($base64);
                $attachment->setType($emailAttachment['contentType']);
                $attachment->setFilename($emailAttachment['fileName']);
                $mail->addAttachment($attachment);
            }
        }

        $mail->setMailSettings($mail_settings);

        $sendGrid = new \SendGrid($this->apiKey);
        $response = $sendGrid->client->mail()->send()->post($mail);
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
