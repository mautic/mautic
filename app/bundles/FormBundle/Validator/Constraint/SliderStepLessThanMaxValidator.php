<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Validator\Constraint;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class SliderStepLessThanMaxValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SliderStepLessThanMax) {
            throw new UnexpectedTypeException($constraint, SliderStepLessThanMax::class);
        }

        $form = $this->resolveParentForm();
        if (!$form instanceof FormInterface) {
            return;
        }

        $max = $form->has('max') ? $form->get('max')->getData() : null;
        if (null === $max || '' === $max) {
            return;
        }

        if (!is_numeric($max) || !is_numeric($value)) {
            return;
        }

        $max  = (int) $max;
        $step = (int) $value;

        if ($step >= $max) {
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
