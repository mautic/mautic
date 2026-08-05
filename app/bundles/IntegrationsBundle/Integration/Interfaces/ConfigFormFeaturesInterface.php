<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Integration\Interfaces;

interface ConfigFormFeaturesInterface
{
    public const FEATURE_SYNC          = 'sync';

    public const FEATURE_PUSH_ACTIVITY = 'push_activity';

    public const FEATURE_CLOUD_STORAGE = 'cloud_storage';

    /**
     * Return an array of value => label pairs for the features this integration supports.
     */
    public function getSupportedFeatures(): array;
}
