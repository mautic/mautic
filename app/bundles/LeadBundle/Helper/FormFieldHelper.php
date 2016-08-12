<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Translation\TranslatorInterface;

class FormFieldHelper
{

    /**
     * @var array
     */
    static private $types = [
        'text'     => [
            'properties' => []
        ],
        'textarea' => [
            'properties' => []
        ],
        'select'   => [
            'properties' => [
                'list' => [
                    'required'  => true,
                    'error_msg' => 'mautic.lead.field.select.listmissing'
                ]
            ]
        ],
        'boolean'  => [
            'properties' => [
                'yes' => [
                    'required'  => true,
                    'error_msg' => 'mautic.lead.field.boolean.yesmissing'
                ],
                'no'  => [
                    'required'  => true,
                    'error_msg' => 'mautic.lead.field.boolean.nomissing'
                ]
            ]
        ],
        'lookup'   => [
            'properties' => [
                'list' => []
            ]
        ],
        'date'     => [
            'properties' => [
                'format' => []
            ]
        ],
        'datetime' => [
            'properties' => [
                'format' => []
            ]
        ],
        'time'     => [
            'properties' => []
        ],
        'timezone' => [
            'properties' => []
        ],
        'email'    => [
            'properties' => []
        ],
        'number'   => [
            'properties' => [
                'roundmode' => [],
                'precision' => []
            ]
        ],
        'tel'      => [
            'properties' => []
        ],
        'url'      => [
            'properties' => []
        ],
        'country'  => [
            'properties' => []
        ],
        'region'   => [
            'properties' => []
        ],
        'timezone' => [
            'properties' => []
        ],
        'locale'   => [
            'properties' => []
        ]
    ];

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
        $choices = [];
        foreach (self::$types as $v => $type) {
            $choices[$v] = $this->translator->transConditional("mautic.core.type.{$v}", "mautic.lead.field.type.{$v}");
        }
        asort($choices);

        return $choices;
    }

    /**
     * @param $type
     * @param $properties
     *
     * @return bool
     */
    static public function validateProperties($type, &$properties)
    {
        if (!array_key_exists($type, self::$types)) {
            //ensure the field type is supported
            return [false, 'mautic.lead.field.typenotrecognized'];
        }

        $fieldType = self::$types[$type];
        foreach ($properties as $key => $value) {
            if (!array_key_exists($key, $fieldType['properties'])) {
                unset($properties[$key]);
            }

            if (!empty($fieldType['properties'][$key]['required']) && empty($value)) {
                //ensure requirements are met
                return [false, $fieldType['properties'][$key]['error_msg']];
            }
        }

        return [true, ''];
    }

    /**
     * @return array
     */
    static public function getCountryChoices()
    {
        $countryJson = file_get_contents(__DIR__.'/../../CoreBundle/Assets/json/countries.json');
        $countries   = json_decode($countryJson);

        $choices = array_combine($countries, $countries);

        return $choices;
    }

    /**
     * @return array
     */
    static public function getRegionChoices()
    {
        $regionJson = file_get_contents(__DIR__.'/../../CoreBundle/Assets/json/regions.json');
        $regions    = json_decode($regionJson);

        $choices = [];
        foreach ($regions as $country => &$regionGroup) {
            $choices[$country] = array_combine($regionGroup, $regionGroup);
        }

        return $choices;
    }

    /**
     * Symfony deprecated and changed Symfony\Component\Form\Extension\Core\Type\TimezoneType::getTimezones to private
     * in 3.0 - so duplicated code here
     *
     * @return array
     */
    static public function getTimezonesChoices()
    {
        static $timezones;

        if (null === $timezones) {
            $timezones = [];

            foreach (\DateTimeZone::listIdentifiers() as $timezone) {
                $parts = explode('/', $timezone);

                if (count($parts) > 2) {
                    $region = $parts[0];
                    $name   = $parts[1].' - '.$parts[2];
                } elseif (count($parts) > 1) {
                    $region = $parts[0];
                    $name   = $parts[1];
                } else {
                    $region = 'Other';
                    $name   = $parts[0];
                }

                $timezones[$region][str_replace('_', ' ', $name)] = $timezone;
            }
        }

        return $timezones;
    }

    /**
     * Get locale choices
     *
     * @return array
     */
    static function getLocaleChoices()
    {
        return Intl::getLocaleBundle()->getLocaleNames();
    }
}