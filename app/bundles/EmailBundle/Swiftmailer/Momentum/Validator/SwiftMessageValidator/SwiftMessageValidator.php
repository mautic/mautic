<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Validator\SwiftMessageValidator;

use Mautic\EmailBundle\Swiftmailer\Momentum\Exception\Validator\SwiftMessageValidator\SwiftMessageValidationException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class SwiftMessageValidator.
 */
final class SwiftMessageValidator implements SwiftMessageValidatorInterface
{
    /**
     * MomentumSwiftMessageValidator constructor.
     */
    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * @throws SwiftMessageValidationException
     */
    public function validate(\Swift_Mime_SimpleMessage $message)
    {
        if (empty($message->getSubject())) {
            throw new SwiftMessageValidationException($this->translator->trans('mautic.email.subject.notblank', [], 'validators'));
        }
    }
}
