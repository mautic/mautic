<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Service;

use Mautic\EmailBundle\Helper\PlainTextMassageHelper;
use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class SwiftMessageService.
 */
final class SwiftMessageService implements SwiftMessageServiceInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * MomentumSwiftMessageService constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;
    }

    /**
     * @param \Swift_Mime_Message $message
     *
     * @return TransmissionDTO
     */
    public function transformToTransmission(\Swift_Mime_Message $message)
    {
        $messageFrom      = $message->getFrom();
        $messageFromEmail = current(array_keys($messageFrom));
        $from             = new TransmissionDTO\ContentDTO\FromDTO($messageFromEmail);
        if (!empty($messageFrom[$messageFromEmail])) {
            $from->setName($messageFrom[$messageFromEmail]);
        }
        $content = new TransmissionDTO\ContentDTO($message->getSubject(), $from);
        if (!empty($message->getBody())) {
            $content->setHtml($message->getBody());
        }
        $headers = $message->getHeaders()->getAll();
        /** @var \Swift_Mime_Header $header */
        foreach ($headers as $header) {
            if ($header->getFieldType() == \Swift_Mime_Header::TYPE_TEXT) {
                $content->addHeader($header->getFieldName(), $header->getFieldBodyModel());
            }
        }
        $messageText = PlainTextMassageHelper::getPlainTextFromMessage($message);
        if (!empty($messageText)) {
            $content->setText($messageText);
        }
        $transmission = new TransmissionDTO($content, 'noreply@mautic.com');
        foreach ($message->getTo() as $email => $name) {
            $transmission->addRecipient(new TransmissionDTO\RecipientDTO($email));
        }

        return $transmission;
    }
}
