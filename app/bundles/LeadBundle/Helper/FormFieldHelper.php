<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

use Symfony\Component\Translation\TranslatorInterface;

class FormFieldHelper
{

    /**
     * @var array
     */
    static private $types = array(
        'text'   => array(
            'label'       => 'mautic.lead.field.type.text',
            'properties'  => array()
        ),
        'select' => array(
            'label'       => 'mautic.lead.field.type.select',
            'properties'  => array(
                'list' => array(
                    'required'  => true,
                    'error_msg' => 'mautic.lead.field.select.listmissing'
                )
            )
        ),
        'boolean'=> array(
            'label'      => 'mautic.lead.field.type.boolean',
            'properties' => array(
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
            'properties'  => array(
                'list' => array()
            )
        ),
        'date'   => array(
            'label'       => 'mautic.lead.field.type.date',
            'properties'  => array(
                'format' => array()
            )
        ),
        'datetime'   => array(
            'label'       => 'mautic.lead.field.type.datetime',
            'properties'  => array(
                'format' => array()
            )
        ),
        'time'   => array(
            'label'       => 'mautic.lead.field.type.time',
            'properties'  => array()
        ),
        'timezone' => array(
            'label'       => 'mautic.lead.field.type.timezone',
            'properties'  => array()
        ),
        'email'  => array(
            'label'       => 'mautic.lead.field.type.email',
            'properties'  => array()
        ),
        'number' => array(
            'label' => 'mautic.lead.field.type.number',
            'properties'  => array(
                'roundmode' => array(),
                'precision' => array()
            )
        ),
        'tel'    => array(
            'label'       => 'mautic.lead.field.type.tel',
            'properties'  => array()
        ),
        'url'    => array(
            'label'       => 'mautic.lead.field.type.url',
            'properties'  => array()
        ),
        'country' => array(
            'label'       => 'mautic.lead.field.type.country',
            'properties'  => array()
        )
    );

    private $translator;

    /**
     * Set translator
     *
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public function getChoiceList()
    {
        $choices = array();
        foreach (self::$types as $v => $type) {
            $choices[$v] = $this->translator->trans($type['label']);
        }
        asort($choices);
        return $choices;
    }

    /**
     * @param $type
     * @param $properties
     * @return bool
     */
    static public function validateProperties($type, &$properties)
    {
        if (!array_key_exists($type, self::$types)) {
            //ensure the field type is supported
            return array(false, 'mautic.lead.field.typenotrecognized');
        }

        $fieldType = self::$types[$type];
        foreach ($properties as $key => $value) {
            if (!array_key_exists($key, $fieldType['properties'])) {
                unset($properties[$key]);
            }

            if (!empty($fieldType['properties'][$key]['required']) && empty($value)) {
                //ensure requirements are met
                return array(false, $fieldType['properties'][$key]['error_msg']);
            }
        }
        return array(true, '');
    }
}