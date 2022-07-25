<?php

namespace Mautic\ApiBundle\Form\Validator\Constraints;

use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class OAuthCallbackValidator extends ConstraintValidator
{
    const PATTERN = '~^[0-9a-z].*://(.*?)(:[0-9]+)?(/?|/\S+)$~ixu';

    /**
     * @param mixed $value
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof OAuthCallback) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\OAuthCallback');
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $value = (string) $value;
        if (!preg_match(static::PATTERN, $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->addViolation();
        }
    }
}
