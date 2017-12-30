<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\Mapper;

use Mautic\ConfigBundle\Exception\BadFormConfigException;
use Mautic\ConfigBundle\Mapper\Helper\ConfigHelper;
use Mautic\ConfigBundle\Mapper\Helper\RestrictionHelper;
use Mautic\CoreBundle\Helper\CoreParametersHelper;

class ConfigMapper
{
    /**
     * @var CoreParametersHelper
     */
    private $parametersHelper;

    /**
     * @var array
     */
    private $restrictedParameters;

    /**
     * ConfigHelper constructor.
     *
     * @param CoreParametersHelper $parametersHelper
     */
    public function __construct(CoreParametersHelper $parametersHelper, array $restrictedParameters = [])
    {
        $this->parametersHelper     = $parametersHelper;
        $this->restrictedParameters = RestrictionHelper::prepareRestrictions($restrictedParameters);
    }

    /**
     * @param array $forms
     *
     * @return array
     *
     * @throws BadFormConfigException
     */
    public function bindFormConfigsWithRealValues(array $forms)
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
     *
     * @param array $formParameters
     *
     * @return array
     */
    private function mergeWithLocalParameters(array $formParameters)
    {
        $formParameters = RestrictionHelper::applyRestrictions($formParameters, $this->restrictedParameters);

        // All config values are stored at root level of the config
        foreach ($formParameters as $formKey => $defaultValue) {
            $configValue = $this->parametersHelper->getParameter($formKey);

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
