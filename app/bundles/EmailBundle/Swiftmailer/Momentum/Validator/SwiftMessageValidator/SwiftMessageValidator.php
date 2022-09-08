<?php

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Validator\SwiftMessageValidator;

use Mautic\EmailBundle\Swiftmailer\Momentum\Exception\Validator\SwiftMessageValidator\SwiftMessageValidationException;

/**
 * Class SwiftMessageValidator.
 */
final class SwiftMessageValidator implements SwiftMessageValidatorInterface
{
    /**
     * @var \Symfony\Contracts\Translation\TranslatorInterface
     */
    private $translator;

    /**
     * MomentumSwiftMessageValidator constructor.
     */
    public function __construct(
        \Symfony\Contracts\Translation\TranslatorInterface $translator
    ) {
        $this->translator = $translator;
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
