<?php

namespace Mautic\ConfigBundle\Mapper;

use Mautic\ConfigBundle\Exception\BadFormConfigException;
use Mautic\ConfigBundle\Mapper\Helper\ConfigHelper;
use Mautic\ConfigBundle\Mapper\Helper\RestrictionHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;

class ConfigMapper
{
    /**
     * @var mixed[]
     */
    private array $restrictedParameters;

    public function __construct(
        private CoreParametersHelper $parametersHelper,
        array $restrictedParameters = []
    ) {
        $this->restrictedParameters = RestrictionHelper::prepareRestrictions($restrictedParameters);
    }

    /**
     * @throws BadFormConfigException
     */
    public function bindFormConfigsWithRealValues(array $forms): array
    {
        foreach ($forms as $bundle => $config) {
            if (!isset($config['parameters'])) {
                throw new BadFormConfigException();
            }

            $forms[$bundle]['parameters'] = $this->mergeWithLocalParameters($forms[$bundle]['parameters']);
        }

        return $forms;
    }

    /**
     * Merges default parameters from each subscribed bundle with the local (real) params.
     */
    private function mergeWithLocalParameters(array $formParameters): array
    {
        $formParameters = RestrictionHelper::applyRestrictions($formParameters, $this->restrictedParameters);

        // All config values are stored at root level of the config
        foreach ($formParameters as $formKey => $defaultValue) {
            $configValue = $this->parametersHelper->get($formKey);

            if (null === $configValue) {
                // Nothing has been locally configured so keep default
                continue;
            }

            // Form field is a collection of parameters
            if (is_array($configValue)) {
                // Apply nested restrictions to nested config values
                $configValue = RestrictionHelper::applyRestrictions($configValue, $this->restrictedParameters, $formKey);

                // Bind configured values with defaults
                $formParameters[$formKey] = ConfigHelper::bindNestedConfigValues($configValue, $defaultValue);

                continue;
            }

            // Form field
            $formParameters[$formKey] = $configValue;
        }

        return $formParameters;
    }
}
