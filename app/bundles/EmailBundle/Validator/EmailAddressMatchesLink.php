<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class EmailAddressMatchesLink extends Constraint
{
    public string $message = 'mautic.email.address.does.not.match.link';

    public string $secretHash;

    public ?string $statEmailAddress = null;
}
