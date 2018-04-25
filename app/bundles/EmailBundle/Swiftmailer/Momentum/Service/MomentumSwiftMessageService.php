<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Service;

use Mautic\EmailBundle\Swiftmailer\Momentum\Exception\MomentumSwiftMessageValidationException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class SwiftMessageService.
 */
final class MomentumSwiftMessageService implements MomentumSwiftMessageServiceInterface
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

    public function getMomentumMessage(\Swift_Mime_Message $message)
    {
    }

    /**
     * @param \Swift_Mime_Message $message
     *
     * @throws MomentumSwiftMessageValidationException
     */
    public function validate(\Swift_Mime_Message $message)
    {
        if (empty($message['subject'])) {
            throw new MomentumSwiftMessageValidationException($this->translator->trans('mautic.email.subject.notblank', [], 'validators'));
        }
    }
}
