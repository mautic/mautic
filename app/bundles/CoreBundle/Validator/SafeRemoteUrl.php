<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Validator;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class SafeRemoteUrl extends Constraint
{
    public string $message;

    /**
     * @param string[]|null $groups
     */
    #[HasNamedArguments]
    public function __construct(
        string $message = 'mautic.core.remote_url_not_allowed',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->message = $message;
    }
}
