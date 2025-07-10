<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Form\Validator\Constraints;

use Mautic\UserBundle\Model\PasswordStrengthEstimatorModel;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class NotWeakValidator extends ConstraintValidator
{
    public function __construct(private PasswordStrengthEstimatorModel $passwordStrengthEstimatorModel)
    {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotWeak) {
            throw new UnexpectedTypeException($constraint, NotWeak::class);
        }

        if ($this->passwordStrengthEstimatorModel->validate($value, $constraint->score)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setCode(NotWeak::TOO_WEAK)
            ->addViolation();
    }
}
