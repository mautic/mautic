<?php

namespace Mautic\PluginBundle\Form\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class CanPublish extends Constraint
{
    public string $message = 'You are not allowed to publish plugin due to insufficient configurations.';
}
