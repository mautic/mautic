<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class SegmentUsedInCampaigns extends Constraint
{
    public function getTargets(): string
    {
        return static::CLASS_CONSTRAINT;
    }
}
