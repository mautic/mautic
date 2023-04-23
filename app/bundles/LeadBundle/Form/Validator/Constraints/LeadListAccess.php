<?php

namespace Mautic\LeadBundle\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @\Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor
 */
class LeadListAccess extends Constraint
{
    public string $message = 'mautic.lead.lists.failed';

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function validatedBy()
    {
        return 'leadlist_access';
    }
}
