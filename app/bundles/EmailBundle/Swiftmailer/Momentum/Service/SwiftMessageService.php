<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Service;

use Mautic\EmailBundle\Helper\PlainTextMassageHelper;
use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO;
use Mautic\EmailBundle\Swiftmailer\Momentum\Metadata\MetadataProcessor;
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

        $metadataProcessor = new MetadataProcessor($message);

        if ($body = $message->getBody()) {
            $content->setHtml($body);
        }

        $headers = $message->getHeaders()->getAll();
        /** @var \Swift_Mime_Header $header */
        foreach ($headers as $header) {
            if ($header->getFieldType() == \Swift_Mime_Header::TYPE_TEXT &&
                !in_array($header->getFieldName(), [
                'Content-Transfer-Encoding',
                'MIME-Version',
                'Subject',
            ])) {
                $content->addHeader($header->getFieldName(), $header->getFieldBodyModel());
            }
        }

        if ($messageText = PlainTextMassageHelper::getPlainTextFromMessage($message)) {
            $content->setText($messageText);
        }

        $returnPath   = $message->getReturnPath() ? $message->getReturnPath() : $messageFromEmail;
        $transmission = new TransmissionDTO($content, $returnPath);

        foreach ($message->getTo() as $email => $name) {
            $recipientDTO = new TransmissionDTO\RecipientDTO(
                $email,
                $metadataProcessor->getMetadata($email),
                $metadataProcessor->getSubstitutionData($email)
            );

            $transmission->addRecipient($recipientDTO);
        }

        return $transmission;
    }
}
