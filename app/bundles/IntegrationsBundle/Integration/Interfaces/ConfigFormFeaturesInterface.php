<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Integration\Interfaces;

interface ConfigFormFeaturesInterface
{
    public const string FEATURE_SYNC          = 'sync';

    public const string FEATURE_PUSH_ACTIVITY = 'push_activity';

    /**
     * Return an array of value => label pairs for the features this integration supports.
     */
    public function getSupportedFeatures(): array;
}
