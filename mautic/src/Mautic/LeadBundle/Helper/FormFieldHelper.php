<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

class FormFieldHelper
{

    /**
     * @var array
     */
    static private $types = array(
        'text'   => array(
            'label'       => 'mautic.lead.field.type.text',
            'definitions' => array()
        ),
        'select' => array(
            'label' => 'mautic.lead.field.type.select',
            'definitions' => array(
                'list' => array(
                    'required'  => true,
                    'error_msg' => 'mautic.lead.field.select.listmissing'
                )
            )
        ),
        'boolean'=> array(
            'label' => 'mautic.lead.field.type.boolean',
            'definitions' => array(
                'yes' => array(
                    'required'  => true,
                    'error_msg' => 'mautic.lead.field.boolean.yesmissing'
                ),
                'no'  => array(
                    'required'  => true,
                    'error_msg' => 'mautic.lead.field.boolean.nomissing'
                )
            )
        ),
        'lookup' => array(
            'label'       => 'mautic.lead.field.type.lookup',
            'definitions' => array(
                'list' => array()
            )
        ),
        'date'   => array(
            'label'       => 'mautic.lead.field.type.date',
            'definitions' => array()
        ),
        'email'  => array(
            'label'       => 'mautic.lead.field.type.email',
            'definitions' => array()
        ),
        'number' => array(
            'label' => 'mautic.lead.field.type.number',
            'definitions' => array(
                'roundmode' => array(),
                'precision' => array()
            )
        ),
        'tel'    => array(
            'label'       => 'mautic.lead.field.type.tel',
            'definitions' => array()
        ),
        'url'    => array(
            'label'       => 'mautic.lead.field.type.url',
            'definitions' => array()
        )
    );

    /**
     * @return array
     */
    static public function getChoiceList()
    {
        $choices = array();

        foreach (self::$types as $v => $type) {
            $choices[$v] = $type['label'];
        }

        return $choices;
    }

    /**
     * @param $type
     * @param $definitions
     * @return bool
     */
    static public function validateDefinitions($type, $definitions)
    {
        if (!array_key_exists($type, self::$types)) {
            //ensure the field type is supported
            return array(false, 'mautic.lead.field.typenotrecognized');
        }

        $fieldType = static::$types[$type];
        foreach ($definitions as $key => $value) {
            if (!array_key_exists($key, $fieldType['definitions'])) {
                //ensure the definitions are recognized
                return array(false, 'mautic.lead.field.keynotrecognized');
            }

            if (!empty($fieldType['definitions'][$key]['required']) && empty($value)) {
                //ensure requirements are met
                return array(false, $fieldType['definitions'][$key]['error_msg']);
            }
        }
        return array(true, '');
    }
}