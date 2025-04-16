<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Validator\Constraints;

use Mautic\DynamicContentBundle\DynamicContent\TypeList;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\ChoiceValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class TypeChoiceValidator extends ChoiceValidator
{
    public function __construct(private TypeList $typeList)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!is_string($value)) {
            return;
        }

        if (!$constraint instanceof Choice) {
            throw new UnexpectedTypeException($constraint, Choice::class);
        }

        if (null === $constraint->choices) {
            $constraint->choices = array_values($this->typeList->getChoices());
        }

        parent::validate(mb_strtolower($value), $constraint);
    }
}
