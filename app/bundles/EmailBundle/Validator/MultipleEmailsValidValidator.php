<?php

namespace Mautic\EmailBundle\Validator;

use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\EmailValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MultipleEmailsValidValidator extends ConstraintValidator
{
    public function __construct(
        private EmailValidator $emailValidator
    ) {
    }

    /**
     * @param string $emailsInString
     */
    public function validate($emailsInString, Constraint $constraint): void
    {
        if (!$emailsInString) {
            return;
        }

        $transformer = new ArrayStringTransformer();
        $emails      = $transformer->reverseTransform($emailsInString);

        foreach ($emails as $email) {
            try {
                $this->emailValidator->validate($email);
            } catch (InvalidEmailException $e) {
                $this->context->buildViolation('mautic.email.multiple_emails.not_valid', ['%email%' => $e->getMessage()])
                    ->addViolation();

                return;
            }
        }
    }
}
