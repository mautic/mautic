<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Helper;

use Mautic\FormBundle\Model\FormModel;

class PropertiesAccessor
{
    public function __construct(
        private readonly FormModel $formModel,
    ) {
    }

    /**
     * @param array<string, mixed> $field
     *
     * @return mixed[]
     */
    public function getProperties(array $field)
    {
        $hasContactFieldMapped = !empty($field['mappedField']) && !empty($field['mappedObject']) && 'contact' === $field['mappedObject'];
        if ('country' === $field['type'] || ($hasContactFieldMapped && !empty($field['properties']['syncList']))) {
            return $this->formModel->getContactFieldPropertiesList((string) $field['mappedField']);
        }
        if (!empty($field['properties'])) {
            return $this->getOptionsListFromProperties($field['properties']);
        }

        return [];
    }

    /**
     * @param string|array<string, string>|list<string|array{label: string, alias: string}|array{label: string, value: string}|list<string>> $options
     *
     * @return array<string, string>
     */
    public function getChoices($options): array
    {
        if (is_string($options)) {
            return $this->getChoicesFromList(explode('|', $options));
        }

        // A missing first numeric index means we already have an associative value=>label map.
        if (!array_is_list($options)) {
            return array_flip($options);
        }

        return $this->getChoicesFromList($options);
    }

    /**
     * @param list<string|array{label: string, alias: string}|array{label: string, value: string}|list<string>> $options
     *
     * @return array<string, string>
     */
    private function getChoicesFromList(array $options): array
    {
        $choices = [];

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
        }
        if (!empty($properties['optionlist']['list'])) {
            return $properties['optionlist']['list'];
        }

        return [];
    }
}
