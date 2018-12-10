<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Service;

use Mautic\EmailBundle\Helper\PlainTextMessageHelper;
use Mautic\EmailBundle\Swiftmailer\Message\MauticMessage;
use Mautic\EmailBundle\Swiftmailer\Momentum\DTO\TransmissionDTO;
use Mautic\EmailBundle\Swiftmailer\Momentum\Metadata\MetadataProcessor;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class SwiftMessageService.
 */
final class SwiftMessageService implements SwiftMessageServiceInterface
{
    private $reservedKeys = [
        'MIME-Version',
        'Content-Type',
        'Content-Transfer-Encoding',
        'To',
        'From',
        'Subject',
        'Reply-To',
        'BCC',
    ];

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

        // Process metadata before consuming subject, body, etc
        $metadataProcessor = new MetadataProcessor($message);

        $content = new TransmissionDTO\ContentDTO($message->getSubject(), $from);
        if ($body = $message->getBody()) {
            $content->setHtml($body);
        }

        $headers = $message->getHeaders()->getAll();
        /** @var \Swift_Mime_Header $header */
        foreach ($headers as $header) {
            if ($header->getFieldType() == \Swift_Mime_Header::TYPE_TEXT && !in_array($header->getFieldName(), $this->reservedKeys)) {
                $content->addHeader($header->getFieldName(), $header->getFieldBodyModel());
            }
        }

        if ($messageText = PlainTextMessageHelper::getPlainTextFromMessage($message)) {
            $content->setText($messageText);
        }

        if ($message instanceof MauticMessage) {
            foreach ($message->getAttachments() as $attachment) {
                if (file_exists($attachment['filePath']) && is_readable($attachment['filePath'])) {
                    try {
                        $swiftAttachment = \Swift_Attachment::fromPath($attachment['filePath']);

                        if (!empty($attachment['fileName'])) {
                            $swiftAttachment->setFilename($attachment['fileName']);
                        }

                        if (!empty($attachment['contentType'])) {
                            $swiftAttachment->setContentType($attachment['contentType']);
                        }

                        if (!empty($attachment['inline'])) {
                            $swiftAttachment->setDisposition('inline');
                        }
                        $attachmentContent = $swiftAttachment->getEncoder()->encodeString($swiftAttachment->getBody());
                        $attachment        = new TransmissionDTO\ContentDTO\AttachementDTO(
                            $swiftAttachment->getContentType(),
                            $swiftAttachment->getFilename(),
                            $attachmentContent
                        );
                        $content->addAttachment($attachment);
                    } catch (\Exception $e) {
                        error_log($e);
                    }
                }
            }
        }

        $cssHeader = $message->getHeaders()->get('X-MC-InlineCSS');
        if ($cssHeader !== null) {
            $content->setInlineCss($cssHeader);
        }

        $returnPath = $message->getReturnPath() ? $message->getReturnPath() : $messageFromEmail;

        $transmission = new TransmissionDTO($content, $returnPath);
        $transmission->setCampaignId($metadataProcessor->getCampaignId());

        $recipientsGrouped = [
            'to'  => (array) $message->getTo(),
            'cc'  => (array) $message->getCc(),
            'bcc' => (array) $message->getBcc(),
        ];

        foreach ($recipientsGrouped as $group => $recipients) {
            $isBcc = ($group === 'bcc');
            foreach ($recipients as $email => $name) {
                $addressDTO   = new TransmissionDTO\RecipientDTO\AddressDTO($email, $name, $isBcc);
                $recipientDTO = new TransmissionDTO\RecipientDTO(
                    $addressDTO,
                    $metadataProcessor->getMetadata($email),
                    $metadataProcessor->getSubstitutionData($email)
                );

                $transmission->addRecipient($recipientDTO);
            }
        }

        if (count($recipientsGrouped['cc'])) {
            $content->addHeader('CC', implode(',', array_keys($recipientsGrouped['cc'])));
        }

        return $transmission;
    }
}
