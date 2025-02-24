<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class LeadListAccess extends Constraint
{
    public string $message  = 'mautic.lead.lists.failed';
    public bool $allowEmpty = false;

    public function validatedBy()
    {
        return 'leadlist_access';
    }
}
