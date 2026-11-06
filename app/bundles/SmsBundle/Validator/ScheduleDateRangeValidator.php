<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Validator;

use Mautic\SmsBundle\Entity\Sms;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class ScheduleDateRangeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ScheduleDateRange) {
            throw new UnexpectedTypeException($constraint, ScheduleDateRange::class);
        }

        if ($value instanceof Sms) {
            if (!$value->isContinueSending()) {
                return;
            }

            $publishUp   = $value->getPublishUp();
            $publishDown = $value->getPublishDown();
            $path        = 'publishDown';
        } elseif (is_array($value)) {
            if (!($value['continueSending'] ?? true)) {
                return;
            }

            $publishUp   = $value['publishUp'] ?? null;
            $publishDown = $value['publishDown'] ?? null;
            $path        = '[publishDown]';
        } else {
            return;
        }

        if ($publishUp && $publishDown && $publishDown <= $publishUp) {
            $this->context->buildViolation($constraint->message)
                ->atPath($path)
                ->addViolation();
        }
    }
}
