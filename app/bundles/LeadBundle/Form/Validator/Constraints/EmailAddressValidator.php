<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\EmailValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class EmailAddressValidator extends ConstraintValidator
{
    public function __construct(
        private EmailValidator $emailValidator
    ) {
    }

    /**
     * @param mixed $value
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!empty($value)) {
            try {
                $this->emailValidator->validate($value);
            } catch (InvalidEmailException $invalidEmailException) {
                $this->context->addViolation(
                    $invalidEmailException->getMessage()
                );
            }
        }
    }
}
