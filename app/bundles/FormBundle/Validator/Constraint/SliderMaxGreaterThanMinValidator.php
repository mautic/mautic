<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Validator\Constraint;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class SliderMaxGreaterThanMinValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SliderMaxGreaterThanMin) {
            throw new UnexpectedTypeException($constraint, SliderMaxGreaterThanMin::class);
        }

        $form = $this->resolveParentForm();
        if (!$form instanceof FormInterface) {
            return;
        }

        $min = $form->has('min') ? $form->get('min')->getData() : null;
        if (null === $min || '' === $min) {
            return;
        }

        if (!is_numeric($min) || !is_numeric($value)) {
            return;
        }

        $min = (int) $min;
        $max = (int) $value;

        if ($min >= $max) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }

    private function resolveParentForm(): ?FormInterface
    {
        $object = $this->context->getObject();
        if ($object instanceof FormInterface) {
            $parent = $object->getParent();
            if ($parent instanceof FormInterface) {
                return $parent;
            }
        }

        $root = $this->context->getRoot();

        return $root instanceof FormInterface ? $root : null;
    }
}
