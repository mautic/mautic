<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class UniqueCompanySegmentAlias extends Constraint
{
    public string $message = 'This alias is already in use.';

    public string $field   = '';

    public function getTargets(): array|string
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * @return string[]
     */
    public function getRequiredOptions(): array
    {
        return ['field'];
    }

    public function getDefaultOption(): ?string
    {
        return 'field';
    }
}
