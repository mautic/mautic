<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Validator\Constraints;

use Mautic\DynamicContentBundle\Helper\DynamicContentHelper;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class NoNestingValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoNesting) {
            throw new UnexpectedTypeException($constraint, NoNesting::class);
        }

        if (null === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if (preg_match(DynamicContentHelper::DYNAMIC_WEB_CONTENT_REGEX, $value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
