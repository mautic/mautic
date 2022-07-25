<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class FieldAliasKeyword extends Constraint
{
    public $message = 'mautic.lead.field.keyword.invalid';

    public function validatedBy()
    {
        return FieldAliasKeywordValidator::class;
    }
}
