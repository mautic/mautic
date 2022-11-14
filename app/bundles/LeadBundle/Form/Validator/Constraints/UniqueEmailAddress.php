<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class UniqueEmailAddress extends Constraint
{
    public string $message = 'mautic.lead.field.email.is_used';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
