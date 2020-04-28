<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\EmailBundle\Entity\Email;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\PageBundle\Entity\Page;

class PropertiesAccessor
{
    /**
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * PropertiesProcessor constructor.
     *
     * @param FieldModel $fieldModel
     */
    public function __construct(FieldModel $fieldModel)
    {
        $this->fieldModel = $fieldModel;
    }

    /**
     * @param array $field
     *
     * @return array|mixed
     */
    public function getProperties(array $field)
    {
        if (!empty($field['leadField']) && !empty($field['properties']['syncList'])) {
            $contactFields = $this->fieldModel->getObjectFields('Lead');
            foreach ($contactFields as $contactField) {
                if ($contactField['alias'] === $field['leadField']) {
                    return $this->getOptionsListFromProperties($contactField['properties']);
                }
            }
        } elseif (!empty($field['properties'])) {
            return $this->getOptionsListFromProperties($field['properties']);
        }

        return [];
    }

    /**
     * @return array
     */
    public function getChoices(array $options)
    {
        $choices = [];
        foreach ($options as $option) {
            if (is_array($option)) {
                if (isset($option['label']) && isset($option['alias'])) {
                    $choices[$option['alias']] = $option['label'];
                } elseif (isset($option['label']) && isset($option['value'])) {
                    $choices[$option['value']] = $option['label'];
                } else {
                    foreach ($option as $group => $opt) {
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
     * @param array $properties
     *
     * @return array|mixed
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
