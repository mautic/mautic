<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Validator;

use Mautic\CoreBundle\Exception\InvalidValueException;
use Mautic\CoreBundle\Exception\RecordNotFoundException;
use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;
use Mautic\EmailBundle\Exception\InvalidEmailException;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\EmailBundle\Validator\EmailOrEmailTokenList;
use Mautic\LeadBundle\DataObject\ContactFieldToken;
use Mautic\LeadBundle\Validator\CustomFieldTokenValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class EmailOrEmailTokenListValidator extends ConstraintValidator
{
    /**
     * @var EmailValidator
     */
    private $emailValidator;

    /**
     * @var CustomFieldTokeValidator
     */
    private $customFieldTokenValidator;

    /**
     * @var ArrayStringTransformer
     */
    private $transformer;

    public function __construct(
        EmailValidator $emailValidator,
        CustomFieldTokenValidator $customFieldTokenValidator
    ) {
        $this->transformer               = new ArrayStringTransformer();
        $this->emailValidator            = $emailValidator;
        $this->customFieldTokenValidator = $customFieldTokenValidator;
    }

    public function validate($csv, Constraint $constraint)
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
        return function (string $emailOrToken) {
            try {
                // Try to validate if the value is an email address.
                $this->emailValidator->validate($emailOrToken);
            } catch (InvalidEmailException $emailException) {
                try {
                    // The token syntax is validated during creation of new ContactFieldToken object.
                    $contactFieldToken = new ContactFieldToken($emailOrToken);

                    // Validate that the contact field exists and is type of email.
                    $this->customFieldTokenValidator->validateFieldType($contactFieldToken, 'email');

                    // Validate that the token default value is a valid email address if set.
                    if ($contactFieldToken->getDefaultValue()) {
                        $this->emailValidator->validate($contactFieldToken->getDefaultValue());
                    }
                } catch (RecordNotFoundException | InvalidValueException $tokenException) {
                    $this->context->buildViolation(
                        'mautic.email.email_or_token.not_valid',
                        ['%value%' => $emailOrToken, '%details%' => $tokenException->getMessage()]
                    )->addViolation();
                }
            }
        };
    }
}
