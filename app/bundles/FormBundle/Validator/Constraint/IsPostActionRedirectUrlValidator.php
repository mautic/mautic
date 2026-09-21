<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Validator\Constraint;

use Mautic\FormBundle\Finder\Tokens\RedirectUrlTokensFinder;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class IsPostActionRedirectUrlValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly RedirectUrlTokensFinder $redirectUrlTokensFinder,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof IsPostActionRedirectUrl) {
            throw new UnexpectedTypeException($constraint, IsPostActionRedirectUrl::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $url = $value;

        if ($this->redirectUrlTokensFinder->hasTokens($value)) {
            $url = $this->redirectUrlTokensFinder->replaceTokensWithDummyData($value);
        }

        $this->validateRegularUrl($url, $constraint->message);
    }

    private function validateRegularUrl(string $url, string $invalidUrlMessage): void
    {
        $urlConstraint = new Url(message: $invalidUrlMessage);
        $violationList = $this->validator->validate($url, $urlConstraint);

        if (0 === $violationList->count()) {
            return;
        }

        foreach ($violationList as $violation) {
            $this
                ->context
                ->buildViolation($violation->getMessage())
                ->setParameters($violation->getParameters())
                ->addViolation();
        }
    }
}
