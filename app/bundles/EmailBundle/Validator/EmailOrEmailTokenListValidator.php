<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Validator;

use Mautic\CoreBundle\Exception\InvalidValueException;
use Mautic\CoreBundle\Exception\RecordException;
use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\LeadBundle\DataObject\ContactFieldToken;
use Mautic\LeadBundle\Exception\InvalidContactFieldTokenException;
use Mautic\LeadBundle\Validator\CustomFieldValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class EmailOrEmailTokenListValidator extends ConstraintValidator
{
    private ArrayStringTransformer $transformer;

    public function __construct(
        private EmailValidator $emailValidator,
        private CustomFieldValidator $customFieldValidator,
    ) {
        $this->transformer = new ArrayStringTransformer();
    }

    public function validate(mixed $csv, Constraint $constraint): void
    {
        if (!$constraint instanceof EmailOrEmailTokenList) {
            throw new UnexpectedTypeException($constraint, EmailOrEmailTokenList::class);
        }

        if (null === $csv || '' === $csv) {
            return;
        }

        if (!is_string($csv)) {
            throw new UnexpectedTypeException($csv, 'string');
        }

        array_map(
            $this->makeEmailOrEmailTokenValidator(),
            $this->transformer->reverseTransform($csv)
        );
    }

    private function makeEmailOrEmailTokenValidator(): callable
    {
        return function (string $emailOrToken): void {
            try {
                // Try to validate if the value is an email address.
                $this->emailValidator->validate($emailOrToken);
            } catch (InvalidEmailException) {
                try {
                    // The token syntax is validated during creation of new ContactFieldToken object.
                    $contactFieldToken = new ContactFieldToken($emailOrToken);

                    // Validate that the token default value is a valid email address if set.
                    if ($contactFieldToken->getDefaultValue()) {
                        $this->emailValidator->validate($contactFieldToken->getDefaultValue());
                    }

                    // Validate that the contact field exists and is type of email.
                    $this->customFieldValidator->validateFieldType($contactFieldToken->getFieldAlias(), 'email');
                } catch (RecordException|InvalidValueException|InvalidContactFieldTokenException $tokenException) {
                    $this->context->addViolation(
                        'mautic.email.email_or_token.not_valid',
                        ['%value%' => $emailOrToken, '%details%' => $tokenException->getMessage()]
                    );
                }
            }
        };
    }
}
