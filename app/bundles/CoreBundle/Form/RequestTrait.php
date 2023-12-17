<?php

namespace Mautic\CoreBundle\Form;

use Mautic\CoreBundle\Form\Type\BooleanType;
use Mautic\CoreBundle\Form\Type\CountryType;
use Mautic\CoreBundle\Form\Type\LocaleType;
use Mautic\CoreBundle\Form\Type\MultiselectType;
use Mautic\CoreBundle\Form\Type\RegionType;
use Mautic\CoreBundle\Form\Type\SelectType;
use Mautic\CoreBundle\Form\Type\TimezoneType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;

trait RequestTrait
{
    /**
     * @param FormInterface<object> $form
     * @param array<mixed>          $params
     * @param array<mixed>          $masks
     * @param array<mixed>          $fields
     *
     * @throws \Exception
     */
    protected function prepareParametersFromRequest(FormInterface $form, array &$params, object $entity = null, array $masks = [], array $fields = []): void
    {
        // ungroup fields if need it
        foreach ($fields as $key=>$field) {
            if (is_array($field)) {
                foreach ($field as $k=>$f) {
                    $fields[$k]=$f;
                }
                unset($fields[$key]);
                continue;
            }
        }

        // Special handling of some fields
        foreach ($form as $name => $child) {
            if (!isset($params[$name])) {
                continue;
            }

            $type = $child->getConfig()->getType();
            if ($type instanceof ResolvedFormTypeInterface) {
                $type = $type->getInnerType();
            }
            switch ($type::class) {
                case YesNoButtonGroupType::class:
                case BooleanType::class:
                    if (!is_object($entity)) {
                        break;
                    }

                    $setter = 'set'.ucfirst($name);
                    // Symfony fails to recognize true values on PATCH and add support for all boolean types (on, off, true, false, 1, 0)
                    // If value is array and count 1, return value of array as string
                    if (is_array($params[$name]) && 1 == count($params[$name])) {
                        $params[$name] = end($params[$name]);
                    }

                    if ('' === $params[$name]) {
                        break;
                    }

                    // find property by value
                    if (!empty($fields)) {
                        $properties = ArrayHelper::getValue('properties', $fields[$name]);
                        if (is_array($properties)) {
                            $valuesAsKeys = array_flip(array_values($properties));
                            if (isset($valuesAsKeys[$params[$name]])) {
                                $params[$name] = $valuesAsKeys[$params[$name]];
                            }
                        }
                    }

                    $data = filter_var($params[$name], FILTER_VALIDATE_BOOLEAN);
                    $data = (bool) $data;
                    try {
                        $entity->$setter($data);
                        // Manually handled so remove from form processing
                        unset($form[$name], $params[$name]);
                        break;
                    } catch (\InvalidArgumentException) {
                    }

                    // If not manually handled cast to int because Symfony form processing take false as empty
                    $params[$name] = (int) $data;

                    break;
                case ChoiceType::class:
                case CountryType::class:
                case LocaleType::class:
                case MultiselectType::class:
                case RegionType::class:
                case SelectType::class:
                case TimezoneType::class:
                    if (!$child->getConfig()->getOption('multiple')) {
                        break;
                    }

                    // Ensure the value is an array
                    if (!is_array($params[$name])) {
                        $params[$name] = (str_contains($params[$name], '|')) ? explode('|', $params[$name]) : ($params[$name] ? [$params[$name]] : []);
                    }

                    break;
                case DateTimeType::class:
                case DateType::class:
                case TimeType::class:
                    // Prevent zero based date placeholders
                    $dateTest = (int) str_replace(['/', '-', ' '], '', $params[$name]);

                    if (!$dateTest) {
                        // Date placeholder was used so just ignore it to allow import of the field
                        unset($params[$name]);
                        break;
                    }

                    if (false === ($timestamp = strtotime($params[$name]))) {
                        $timestamp = null;
                    }

                    if (!$timestamp) {
                        unset($params[$name]);
                        break;
                    }

                    switch ($type::class) {
                        case DateTimeType::class:
                            $params[$name] = (new \DateTime(date('Y-m-d H:i:s', $timestamp)))->format('Y-m-d H:i');
                            break;
                        case DateType::class:
                            $params[$name] = (new \DateTime(date('Y-m-d', $timestamp)))->format('Y-m-d');
                            break;
                        case TimeType::class:
                            $params[$name] = (new \DateTime(date('H:i:s', $timestamp)))->format('H:i:s');
                            break;
                    }
                    break;
            }
        }

        if (!isset($masks['description'])) {
            // Add description to support strict HTML
            $masks['description'] = 'strict_html';
        }

        if (!isset($masks['content'])) {
            // Assume HTML
            $masks['description'] = 'html';
        }

        $params = InputHelper::_($params, $masks);
    }

    /**
     * @param array<mixed> $fieldData
     * @param array<mixed> $leadField
     */
    public function cleanFields(array &$fieldData, array $leadField): void
    {
        // This will catch null values or non-existent values to prevent null from converting to false/0
        if (!isset($fieldData[$leadField['alias']])) {
            return;
        }

        switch ($leadField['type']) {
            case 'boolean':
                $fieldData[$leadField['alias']] = InputHelper::boolean($fieldData[$leadField['alias']]);
                break;
                // Ensure date/time entries match what symfony expects
            case 'datetime':
            case 'date':
            case 'time':
                // Prevent zero based date placeholders
                $dateTest = (int) str_replace(['/', '-', ' '], '', $fieldData[$leadField['alias']]);
                if (!$dateTest) {
                    // Date placeholder was used so just ignore it to allow import of the field
                    unset($fieldData[$leadField['alias']]);
                } else {
                    if (false === ($timestamp = strtotime($fieldData[$leadField['alias']]))) {
                        $timestamp = null;
                    }
                    if ($timestamp) {
                        switch ($leadField['type']) {
                            case 'datetime':
                                $fieldData[$leadField['alias']] = (new \DateTime(date('Y-m-d H:i:s', $timestamp)))->format('Y-m-d H:i:s');
                                break;
                            case 'date':
                                $fieldData[$leadField['alias']] = (new \DateTime(date('Y-m-d', $timestamp)))->format('Y-m-d');
                                break;
                            case 'time':
                                $fieldData[$leadField['alias']] = (new \DateTime(date('H:i:s', $timestamp)))->format('H:i:s');
                                break;
                        }
                    }
                }
                break;
            case 'multiselect':
                if (!is_array($fieldData[$leadField['alias']])) {
                    if (str_contains($fieldData[$leadField['alias']], '|')) {
                        $fieldData[$leadField['alias']] = explode('|', $fieldData[$leadField['alias']]);
                    } else {
                        $fieldData[$leadField['alias']] = [$fieldData[$leadField['alias']]];
                    }
                }
                break;
            case 'number':
                $fieldData[$leadField['alias']] = (float) $fieldData[$leadField['alias']];
                break;
            case 'email':
                $fieldData[$leadField['alias']] = InputHelper::email($fieldData[$leadField['alias']]);
                break;
        }
    }
}
