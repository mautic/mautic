<?php

namespace Mautic\FormBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Phone number constraint.
 */
final class PhoneNumberConstraint extends Constraint
{
    public $message;

    public function getMessage()
    {
        if (null !== $this->message) {
            return $this->message;
        }
    }
}
