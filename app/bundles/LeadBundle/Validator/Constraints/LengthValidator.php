<?php

namespace Mautic\LeadBundle\Validator\Constraints;

use Mautic\LeadBundle\Helper\FormFieldHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\LengthValidator as SymfonyLengthValidator;

class LengthValidator extends SymfonyLengthValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (is_array($value)) {
            $value = FormFieldHelper::formatList(FormFieldHelper::FORMAT_BAR, $value);
        }

        parent::validate($value, $constraint);
    }
}
