<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\ConditionalField\FieldsMatching;

use Mautic\FormBundle\ConditionalField\Enum\ConditionalFieldEnum;
use Mautic\FormBundle\Entity\Field;

class FieldsMatchingFactory
{
    /**
     * @var array|Field[]
     */
    private $fields = [];

    /**
     * @var OptionsProcessor
     */
    private $optionProcessor;

    /**
     * @var array
     */
    private $contactFields;

    /**
     * FieldsMatching constructor.
     *
     * @param array|Field[] $fields
     * @param array         $contactFields
     */
    public function __construct(array $fields, array $contactFields)
    {
        // Just choices, radio groups and country field
        foreach ($fields as $field) {
            if (in_array($field['type'], ConditionalFieldEnum::$conditionalFieldTypes)) {
                $this->fields[$field['alias']] = $field;
            }
        }
        $this->optionProcessor = new OptionsProcessor();
        $this->contactFields   = reset($contactFields);
    }

    /**
     * @return array
     */
    public function getChoices()
    {
        return  $this->optionProcessor->getChoices($this->fields);
    }

    /**
     * @param array $field
     *
     * @return array|mixed
     */
    public function getOptionsForField(array $field)
    {
        if (!empty($field['leadField']) && !empty($field['properties']['syncList'])) {
            foreach ($this->contactFields as $contactField) {
                if ($contactField['alias'] === $field['leadField']) {
                    return $this->getOptionsFromProperties($contactField['properties']);
                }
            }
        } elseif (!empty($field['properties'])) {
            return $this->getOptionsFromProperties($field['properties']);
        }

        return [];
    }

    /**
     * @param array $properties
     *
     * @return array|mixed
     */
    private function getOptionsFromProperties(array $properties)
    {
        if (!empty($properties['list']['list'])) {
            return $properties['list']['list'];
        } elseif (!empty($properties['optionlist']['list'])) {
            return $properties['optionlist']['list'];
        }

        return [];
    }
}
