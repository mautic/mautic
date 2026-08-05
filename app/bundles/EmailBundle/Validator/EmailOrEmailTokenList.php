<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Validator;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class EmailOrEmailTokenList extends Constraint
{
    public bool $allowMultiple;

    /**
     * @param string[]|null $groups
     */
    #[HasNamedArguments]
    public function __construct(
        bool $allowMultiple = true,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->allowMultiple = $allowMultiple;
    }
}
