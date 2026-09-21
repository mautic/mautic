<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class UniqueCustomField extends Constraint
{
    /**
     * @param string[]|null $groups
     */
    #[HasNamedArguments]
    public function __construct(
        public string $object,
        public string $message = 'mautic.lead.field.unique.is_used',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
