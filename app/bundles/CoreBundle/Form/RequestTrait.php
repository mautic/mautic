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
                            $data = filter_var($params[$name], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                            if ($data === null) {
                                throw new \Exception('boolean: value not accepted');
                            }
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
                                $params[$name] = [$params[$name]];
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
                            switch ($type) {
                                case 'datetime':
                                    $params[$name] = (new \DateTime($params[$name]))->format('Y-m-d H:i');
                                    break;
                                case 'date':
                                    $params[$name] = (new \DateTime($params[$name]))->format('Y-m-d');
                                    break;
                                case 'time':
                                    $params[$name] = (new \DateTime($params[$name]))->format('H:i');
                                    break;
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
}
