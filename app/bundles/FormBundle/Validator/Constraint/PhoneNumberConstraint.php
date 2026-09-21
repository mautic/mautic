<?php

namespace Mautic\FormBundle\Validator\Constraint;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

/**
 * Phone number constraint.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class PhoneNumberConstraint extends Constraint
{
    /**
     * @param string[]|null $groups
     */
    #[HasNamedArguments]
    public function __construct(
        public ?string $message = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }
}
