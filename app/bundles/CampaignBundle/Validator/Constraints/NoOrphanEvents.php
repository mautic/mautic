<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class NoOrphanEvents extends Constraint
{
    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
