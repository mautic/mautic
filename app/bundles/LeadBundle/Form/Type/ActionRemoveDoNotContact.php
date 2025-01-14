<?php

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class ActionRemoveDoNotContact extends AbstractType
{
    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'lead_action_removedonotcontact';
    }
}
