<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form;

use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\Form\Form;

trait RequestTrait
{
    /**
     * @param Form  $form
     * @param array $params
     * @param null  $entity
     * @param array $masks
     */
    protected function prepareParametersFromRequest(Form $form, array &$params, $entity = null, $masks = [])
    {
        // Special handling of some fields
        foreach ($form as $name => $child) {
            if (isset($params[$name])) {
                $type = $child->getConfig()->getType()->getName();
                switch ($type) {
                    case 'yesno_button_group':
                        if (is_object($entity)) {
                            $setter = 'set'.ucfirst($name);
                            // Symfony fails to recognize true values on PATCH and add support for all boolean types (on, off, true, false, 1, 0)
                            // If value is array and count 1, return value of array as string
                            if (is_array($params[$name]) && 1 == count($params[$name])) {
                                $params[$name] = end($params[$name]);
                            }

                            if ('' === $params[$name]) {
                                continue;
                            }

                            $data = filter_var($params[$name], FILTER_VALIDATE_BOOLEAN);
                            $data = (bool) $data;
                            try {
                                $entity->$setter($data);
                                // Manually handled so remove from form processing
                                unset($form[$name], $params[$name]);
                                break;
                            } catch (\InvalidArgumentException $exception) {
                            }
                            $params[$name] = $data;
                        }
                        break;
                    case 'choice':
                        if ($child->getConfig()->getOption('multiple')) {
                            // Ensure the value is an array
                            if (!is_array($params[$name])) {
                                if (false !== strpos($params[$name], '|')) {
                                    $params[$name] = explode('|', $params[$name]);
                                } else {
                                    $params[$name] = [$params[$name]];
                                }
                            }
                        }
                        break;
                    case 'datetime':
                    case 'date':
                    case 'time':
                        // Prevent zero based date placeholders
                        $dateTest = (int) str_replace(['/', '-', ' '], '', $params[$name]);

                        if (!$dateTest) {
                            // Date placeholder was used so just ignore it to allow import of the field
                            unset($params[$name]);
                        } else {
                            if (false === ($timestamp = strtotime($params[$name]))) {
                                $timestamp = null;
                            }
                            if ($timestamp) {
                                switch ($type) {
                                    case 'datetime':
                                        $params[$name] = (new \DateTime(date('Y-m-d H:i:s', $timestamp)))->format('Y-m-d H:i:s');
                                        break;
                                    case 'date':
                                        $params[$name] = (new \DateTime(date('Y-m-d', $timestamp)))->format('Y-m-d');
                                        break;
                                    case 'time':
                                        $params[$name] = (new \DateTime(date('H:i:s', $timestamp)))->format('H:i:s');
                                        break;
                                }
                            } else {
                                unset($params[$name]);
                            }
                        }
                        break;
                }
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
     * @param $fieldData
     * @param $leadField
     */
    public function cleanFields(&$fieldData, $leadField)
    {
        // This will catch null values or non-existent values to prevent null from converting to false/0
        if (!isset($fieldData[$leadField['alias']])) {
            return;
        }

        switch ($leadField['type']) {
            // Adjust the boolean values from text to boolean. Do not convert null to false.
            case 'boolean':
                $fieldData[$leadField['alias']] = (int) filter_var($fieldData[$leadField['alias']], FILTER_VALIDATE_BOOLEAN);
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
                    if (false !== strpos($fieldData[$leadField['alias']], '|')) {
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
