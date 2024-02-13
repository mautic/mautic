<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class LeadListAccess extends Constraint
{
    public $message = 'mautic.lead.lists.failed';

    public function validatedBy()
    {
        return 'leadlist_access';
    }
}
