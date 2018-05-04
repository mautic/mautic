<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\DTO;

use Mautic\EmailBundle\Helper\PlainTextMassageHelper;
use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO\RecipientDTO;

/**
 * Class MomentumMessage.
 */
final class MomentumMessage implements \JsonSerializable
{
    /**
     * @var array
     */
    private $content = [];

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var array
     */
    private $recipients = [];

    /**
     * @var array
     */
    private $tags = [];

    /**
     * MomentumMessage constructor.
     *
     * @param \Swift_Mime_Message $swiftMessage
     */
    public function __construct(\Swift_Mime_Message $swiftMessage)
    {
        $this->setContent($swiftMessage);
        $this->setHeaders($swiftMessage);
        $this->setRecipients($swiftMessage);
    }

    /**
     * @return mixed
     */
    public function jsonSerialize()
    {
        return [
            'content'    => $this->content,
            'headers'    => $this->headers,
            'recipients' => $this->recipients,
            'tags'       => $this->tags,
        ];
    }

    /**
     * @param \Swift_Mime_Message $message
     */
    private function setContent(\Swift_Mime_Message $message)
    {
        $from          = $message->getFrom();
        $fromEmail     = current(array_keys($from));
        $fromName      = $from[$fromEmail];
        $this->content = [
            'from'    => (!empty($fromName) ? ($fromName.' <'.$fromEmail.'>') : $fromEmail),
            'subject' => $message->getSubject(),
        ];
        if (!empty($message->getBody())) {
            $this->content['html'] = $message->getBody();
        }
        $messageText = PlainTextMassageHelper::getPlainTextFromMessage($message);
        if (!empty($messageText)) {
            $this->content['text'] = $messageText;
        }
        $encoder = new \Swift_Mime_ContentEncoder_Base64ContentEncoder();
        foreach ($message->getChildren() as $child) {
            if ($child instanceof \Swift_Image) {
                $this->content['inline_images'][] = [
                    'type' => $child->getContentType(),
                    'name' => $child->getId(),
                    'data' => $encoder->encodeString($child->getBody()),
                ];
            }
        }
    }

    /**
     * @param \Swift_Mime_Message $message
     */
    private function setHeaders(\Swift_Mime_Message $message)
    {
        $headers = $message->getHeaders()->getAll();
        /** @var \Swift_Mime_Header $header */
        foreach ($headers as $header) {
            if ($header->getFieldType() == \Swift_Mime_Header::TYPE_TEXT) {
                $this->headers[$header->getFieldName()] = $header->getFieldBodyModel();
            }
        }
    }

    /**
     * @param \Swift_Mime_Message $message
     */
    private function setRecipients(\Swift_Mime_Message $message)
    {
        foreach ($message->getTo() as $email => $name) {
            $recipient          = new RecipientDTO($email);
            $this->recipients[] = $recipient;
        }
    }
}
