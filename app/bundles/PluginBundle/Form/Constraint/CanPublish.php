<?php

namespace Mautic\PluginBundle\Form\Constraint;

use Symfony\Component\Validator\Constraint;

class CanPublish extends Constraint
{
    public string $message = 'You are not allowed to publish plugin due to insufficient configurations.';

    public string $integrationName;

    public function getDefaultOption(): string
    {
        return 'integrationName';
    }
}
