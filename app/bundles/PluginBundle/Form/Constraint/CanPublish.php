<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Form\Constraint;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class CanPublish extends Constraint
{
    public string $message;

    public string $integrationName;

    /**
     * @param string[]|null $groups
     */
    #[HasNamedArguments]
    public function __construct(
        string $integrationName,
        string $message = 'mautic.lead_list.not_allowed_plugin_publish',
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct(null, $groups, $payload);

        $this->integrationName = $integrationName;
        $this->message         = $message;
    }
}
