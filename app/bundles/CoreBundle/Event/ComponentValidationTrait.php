<?php

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Exception\BadConfigurationException;

trait ComponentValidationTrait
{
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
