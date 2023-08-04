<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class SegmentInUse extends Constraint
{
    public $message = 'mautic.lead_list.is_in_use';

    public function validatedBy(): string
    {
        return 'segment_in_use';
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
