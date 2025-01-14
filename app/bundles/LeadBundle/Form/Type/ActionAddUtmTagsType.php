<?php

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class ActionAddUtmTagsType extends AbstractType
{
    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'lead_action_addutmtags';
    }
}
