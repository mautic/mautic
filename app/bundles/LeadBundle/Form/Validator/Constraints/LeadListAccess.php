<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class LeadListAccess extends Constraint
{
    public string $message  = 'mautic.lead.lists.failed';

    public bool $allowEmpty = false;

    /**
     * @param string[]|null $groups
     */
    public function __construct(
        ?bool $allowEmpty = null,
        ?string $message = null,
        ?array $groups = null,
        mixed $payload = null
    )
    {
        parent::__construct(null, $groups, $payload);

        $this->allowEmpty = $allowEmpty ?? $this->allowEmpty;
        $this->message    = $message ?? $this->message;
    }

    public function validatedBy(): string
    {
        return 'leadlist_access';
    }
}
