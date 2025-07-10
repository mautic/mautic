<?php

namespace Mautic\FormBundle\Validator\Constraint;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class FileExtensionConstraintValidator extends ConstraintValidator
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed      $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!is_array($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ forbidden }}', '')
                ->addViolation();
        }

        $blacklistedExtensions = $this->coreParametersHelper->get('blacklisted_extensions');
        $intersect             = array_intersect($value, $blacklistedExtensions);
        if ($intersect) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ forbidden }}', implode(', ', $intersect))
                ->addViolation();
        }
    }
}
