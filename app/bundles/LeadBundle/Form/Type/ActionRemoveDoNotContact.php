<?php

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

/**
 * @extends AbstractType<mixed>
 */
class ActionRemoveDoNotContact extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'lead_action_removedonotcontact';
    }
}
