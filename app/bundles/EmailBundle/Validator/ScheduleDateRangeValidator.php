<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Validator;

use Mautic\EmailBundle\Entity\Email;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ScheduleDateRangeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ScheduleDateRange) {
            throw new UnexpectedTypeException($constraint, ScheduleDateRange::class);
        }

        // Handle Email entity validation
        if ($value instanceof Email) {
            // Skip validation if continueSending is false
            if (!$value->getContinueSending()) {
                return;
            }

            $publishUp   = $value->getPublishUp();
            $publishDown = $value->getPublishDown();
            $pathPrefix  = '';
        }
        // Handle form data validation
        elseif (is_array($value)) {
            // Skip validation if continueSending is false
            if (!($value['continueSending'] ?? true)) {
                return;
            }

            $publishUp   = $value['publishUp'] ?? null;
            $publishDown = $value['publishDown'] ?? null;
            $pathPrefix  = '[publishDown]';
        }

        if ($publishUp && $publishDown && $publishDown <= $publishUp) {
            $this->context->buildViolation($constraint->message)
                ->atPath($pathPrefix ?: 'publishDown')
                ->addViolation();
        }
    }
}
