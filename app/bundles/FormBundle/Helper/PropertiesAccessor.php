<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Helper;

use Mautic\FormBundle\Model\FormModel;

class PropertiesAccessor
{
    public function __construct(
        private FormModel $formModel,
    ) {
    }

    /**
     * @param mixed[] $field
     *
     * @return mixed[]
     */
    public function getProperties(array $field)
    {
        $hasContactFieldMapped = !empty($field['mappedField']) && !empty($field['mappedObject']) && 'contact' === $field['mappedObject'];
        if ('country' === $field['type'] || ($hasContactFieldMapped && !empty($field['properties']['syncList']))) {
            return $this->formModel->getContactFieldPropertiesList((string) $field['mappedField']);
        } elseif (!empty($field['properties'])) {
            return $this->getOptionsListFromProperties($field['properties']);
        }

        return [];
    }

    /**
     * @param string|mixed[] $options
     *
     * @return string[]
     */
    public function getChoices($options): array
    {
        $choices = [];

        if (is_array($options) && !isset($options[0]['value'])) {
            return array_flip($options);
        }

        if (!is_array($options)) {
            $options = explode('|', (string) $options);
        }

        foreach ($options as $option) {
            if (is_array($option)) {
                if (isset($option['label']) && isset($option['alias'])) {
                    $choices[$option['label']] = $option['alias'];
                } elseif (isset($option['label']) && isset($option['value'])) {
                    $choices[$option['label']] = $option['value'];
                } else {
                    foreach ($option as $opt) {
                        $choices[$opt] = $opt;
                    }
                }
            } else {
                $choices[$option] = $option;
            }
        }

        return $choices;
    }

    /**
     * @param array<string,mixed> $properties
     *
     * @return mixed[]
     */
    private function getOptionsListFromProperties(array $properties)
    {
        if (!empty($properties['list']['list'])) {
            return $properties['list']['list'];
        } elseif (!empty($properties['optionlist']['list'])) {
            return $properties['optionlist']['list'];
        }

        return [];
    }
}
