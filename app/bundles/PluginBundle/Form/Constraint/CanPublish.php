<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Form\Constraint;

use Symfony\Component\Validator\Constraint;

class CanPublish extends Constraint
{
    public string $message =  'mautic.lead_list.not_allowed_plugin_publish';

    public string $integrationName;

    public function getDefaultOption(): string
    {
        return 'integrationName';
    }
}
