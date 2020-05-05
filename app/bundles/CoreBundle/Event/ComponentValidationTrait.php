<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Exception\BadConfigurationException;

/**
 * Class ComponentValidationTrait.
 */
trait ComponentValidationTrait
{
    /**
     * @param array $keys
     * @param array $component
     * @param array $methods
     */
    private function verifyComponent(array $keys, array $component, array $callbacks = [])
    {
        foreach ($keys as $k) {
            if (is_array($k)) {
                // Check that at least one option exists
                if (empty(array_intersect($k, array_keys($component)))) {
                    throw new BadConfigurationException("At least one of the following keys are required: '".implode(', ', $k));
                }
            } elseif (!array_key_exists($k, $component)) {
                throw new BadConfigurationException("The key, '$k' is missing.");
            }
        }

        if ($callbacks) {
            foreach ($callbacks as $m) {
                if (isset($component[$m]) && !is_callable($component[$m])) {
                    throw new BadConfigurationException($component[$m].' is not callable.');
                }
            }
        }
    }
}
