<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Validator\SwiftMessageValidator;

use Mautic\EmailBundle\Swiftmailer\Momentum\Exception\Validator\SwiftMessageValidator\SwiftMessageValidationException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class SwiftMessageValidator.
 */
final class SwiftMessageValidator implements SwiftMessageValidatorInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * MomentumSwiftMessageValidator constructor.
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
     * @throws SwiftMessageValidationException
     */
    public function validate(\Swift_Mime_Message $message)
    {
        if (empty($message->getSubject())) {
            throw new SwiftMessageValidationException($this->translator->trans('mautic.email.subject.notblank', [], 'validators'));
        }
    }
}
