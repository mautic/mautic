<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

/**
 * @extends AbstractType<mixed>
 */
final class ActionRemoveDoNotContact extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'lead_action_removedonotcontact';
    }
}
