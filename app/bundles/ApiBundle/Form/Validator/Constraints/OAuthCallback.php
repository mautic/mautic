<?php

namespace Mautic\ApiBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class OAuthCallback extends Constraint
{
    public string $message;

    /**
     * @param string[]|null $groups
     */
    #[HasNamedArguments]
    public function __construct(
        string $message = 'The callback URL is invalid.',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->message = $message;
    }

    public function validatedBy(): string
    {
        return OAuthCallbackValidator::class;
    }
}
