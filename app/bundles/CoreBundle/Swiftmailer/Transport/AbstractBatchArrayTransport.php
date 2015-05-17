<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Swiftmailer\Transport;

use Mautic\CoreBundle\Helper\MailHelper;
use Mautic\CoreBundle\Swiftmailer\Message\MauticMessage;

/**
 * Class AbstractBatchArrayTransport
 */
abstract class AbstractBatchArrayTransport implements InterfaceBatchTransport
{
    /**
     * @var \Swift_Mime_Message
     */
    protected $message;

    /**
     * @param \Swift_Mime_Message $message
     * @param null                $failedRecipients
     *
     * @return int
     * @throws \Exception
     */
    abstract public function send(\Swift_Mime_Message $message, &$failedRecipients = null);

    /**
     * Get the metadata from a MauticMessage
     */
    public function getMetadata()
    {
        return ($this->message instanceof MauticMessage) ? $this->message->getMetadata() : array();
    }

    /**
     * Converts \Swift_Message into associative array
     *
     * @param array          $search   If the mailer requires tokens in another format than Mautic's, pass array of Mautic tokens to replace
     * @param array          $replace  If the mailer requires tokens in another format than Mautic's, pass array of replacement tokens
     *
     * @return array|\Swift_Message
     */
    protected function messageToArray($search = array(), $replace = array())
    {
        if (!empty($search)) {
            MailHelper::searchReplaceTokens($search, $replace, $this->message);
        }

        $from      = $this->message->getFrom();
        $fromEmail = current(array_keys($from));
        $fromName  = $from[$fromEmail];

        $message = array(
            'html'       => $this->message->getBody(),
            'text'       => MailHelper::getPlainText($this->message),
            'subject'    => $this->message->getSubject(),
            'from'       => array(
                'name'  => $fromName,
                'email' => $fromEmail
            )
        );

        // Generate the recipients
        $message['recipients'] = array(
            'to' => array(),
            'cc' => array(),
            'bcc' => array()
        );

        $to = $this->message->getTo();
        foreach ($to as $email => $name) {
            $message['recipients']['to'][] = array(
                'email' => $email,
                'name'  => $name
            );
        }

        $cc = $this->message->getCc();
        if (!empty($cc)) {
            foreach ($cc as $email => $name) {
                $message['recipients']['cc'][] = array(
                    'email' => $email,
                    'name'  => $name
                );
            }
        }

        $bcc = $this->message->getBcc();
        if (!empty($bcc)) {
            foreach ($bcc as $email => $name) {
                $message['recipients']['bcc'][] = array(
                    'email' => $email,
                    'name'  => $name
                );
            }
        }

        $replyTo = $this->message->getReplyTo();
        if (!empty($replyTo)) {
            foreach ($replyTo as $email => $name) {
                $message['replyTo'] = array(
                    'email' => $email,
                    'name'  => $name
                );
            }
        }

        // Attachments
        $children    = $this->message->getChildren();
        $attachments = array();
        foreach ($children as $child) {
            if ($child instanceof \Swift_Attachment) {
                $attachments[] = array(
                    'type'    => $child->getContentType(),
                    'name'    => $child->getFilename(),
                    'content' => $child->getEncoder()->encodeString($child->getBody())
                );
            }
        }
        $message['attachments'] = $attachments;

        return $message;
    }
}