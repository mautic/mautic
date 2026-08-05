<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class UniqueCustomField extends Constraint
{
    public string $message;

    public string $object;

    /**
     * @param string[]|null $groups
     */
    #[HasNamedArguments]
    public function __construct(
        string $object,
        string $message = 'mautic.lead.field.unique.is_used',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->object  = $object;
        $this->message = $message;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
