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
            'label'       => 'mautic.core.text',
            'properties'  => array()
        ),
        'select' => array(
            'label'       => 'mautic.core.select',
            'properties'  => array(
                'list' => array(
                    'required'  => true,
                    'error_msg' => 'mautic.lead.field.select.listmissing'
                )
            )
        ),
        'boolean'=> array(
            'label'      => 'mautic.core.boolean',
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
            'label'       => 'mautic.core.date',
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
            'label'       => 'mautic.core.email',
            'properties'  => array()
        ),
        'number' => array(
            'label' => 'mautic.core.number',
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
            'label'       => 'mautic.core.url',
            'properties'  => array()
        ),
        'country' => array(
            'label'       => 'mautic.lead.field.type.country',
            'properties'  => array()
        ),
        'region' => array(
            'label'       => 'mautic.lead.field.type.region',
            'properties'  => array()
        ),
        'timezone' => array(
            'label'       => 'mautic.lead.field.type.timezone',
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

    /**
     * @return array
     */
    static public function getCountryChoices()
    {
        $countryJson = file_get_contents(__DIR__ . '/../../CoreBundle/Assets/json/countries.json');
        $countries = json_decode($countryJson);

        $choices = array_combine($countries, $countries);
        return $choices;
    }

    /**
     * @return array
     */
    static public function getRegionChoices()
    {
        $regionJson = file_get_contents(__DIR__ . '/../../CoreBundle/Assets/json/regions.json');
        $regions = json_decode($regionJson);

        $choices = array();
        foreach ($regions as $country => &$regionGroup) {
            $choices[$country] = array_combine($regionGroup, $regionGroup);
        }

        return $choices;
    }

    /**
     * @return array
     */
    static public function getTimezonesChoices()
    {
        $tz        = new \Symfony\Component\Form\Extension\Core\Type\TimezoneType();
        $timezones = $tz->getTimezones();

        return $timezones;
    }
}