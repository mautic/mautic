<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class SegmentUsedInCampaigns extends Constraint
{
    public function getTargets(): string|array
    {
        return static::CLASS_CONSTRAINT;
    }
}
