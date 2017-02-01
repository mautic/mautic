<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Helper\AbstractFormFieldHelper;
use Symfony\Component\Intl\Intl;

class FormFieldHelper extends AbstractFormFieldHelper
{
    /**
     * @var array
     */
    private static $types = [
        'text' => [
            'properties' => [],
        ],
        'textarea' => [
            'properties' => [],
        ],
        'multiselect' => [
            'properties' => [
                'list' => [
                    'required'  => true,
                    'error_msg' => 'mautic.lead.field.select.listmissing',
                ],
            ],
        ],
        'select' => [
            'properties' => [
                'list' => [
                    'required'  => true,
                    'error_msg' => 'mautic.lead.field.select.listmissing',
                ],
            ],
        ],
        'boolean' => [
            'properties' => [
                'yes' => [
                    'required'  => true,
                    'error_msg' => 'mautic.lead.field.boolean.yesmissing',
                ],
                'no' => [
                    'required'  => true,
                    'error_msg' => 'mautic.lead.field.boolean.nomissing',
                ],
            ],
        ],
        'lookup' => [
            'properties' => [
                'list' => [],
            ],
        ],
        'date' => [
            'properties' => [
                'format' => [],
            ],
        ],
        'datetime' => [
            'properties' => [
                'format' => [],
            ],
        ],
        'time' => [
            'properties' => [],
        ],
        'timezone' => [
            'properties' => [],
        ],
        'email' => [
            'properties' => [],
        ],
        'number' => [
            'properties' => [
                'roundmode' => [],
                'precision' => [],
            ],
        ],
        'tel' => [
            'properties' => [],
        ],
        'url' => [
            'properties' => [],
        ],
        'country' => [
            'properties' => [],
        ],
        'region' => [
            'properties' => [],
        ],
        'timezone' => [
            'properties' => [],
        ],
        'locale' => [
            'properties' => [],
        ],
    ];

    /**
     * Set the translation key prefix.
     */
    public function setTranslationKeyPrefix()
    {
        $this->translationKeyPrefix = 'mautic.lead.field.type.';
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return self::$types;
    }

    /**
     * @return array
     */
    public static function getListTypes()
    {
        return ['select', 'boolean', 'lookup', 'country', 'region', 'timezone', 'locale'];
    }

    /**
     * @param $type
     * @param $properties
     *
     * @return bool
     */
    public static function validateProperties($type, &$properties)
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
    public static function getCountryChoices()
    {
        $countryJson = file_get_contents(__DIR__.'/../../CoreBundle/Assets/json/countries.json');
        $countries   = json_decode($countryJson);

        $choices = array_combine($countries, $countries);

        return $choices;
    }

    /**
     * @return array
     */
    public static function getRegionChoices()
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
     * in 3.0 - so duplicated code here.
     *
     * @return array
     */
    public static function getTimezonesChoices()
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

                $timezones[$region][$timezone] = str_replace('_', ' ', $name);
            }
        }

        return $timezones;
    }

    /**
     * Get locale choices.
     *
     * @return array
     */
    public static function getLocaleChoices()
    {
        return Intl::getLocaleBundle()->getLocaleNames();
    }

    /**
     * Get date field choices.
     *
     * @return array
     */
    public function getDateChoices()
    {
        $options = [
            'anniversary' => $this->translator->trans('mautic.campaign.event.timed.choice.anniversary'),
            '+P0D'        => $this->translator->trans('mautic.campaign.event.timed.choice.today'),
            '-P1D'        => $this->translator->trans('mautic.campaign.event.timed.choice.yesterday'),
            '+P1D'        => $this->translator->trans('mautic.campaign.event.timed.choice.tomorrow'),
        ];

        $daysOptions = [];
        for ($dayInterval = 2; $dayInterval <= 31; ++$dayInterval) {
            $daysOptions['+P'.$dayInterval.'D'] = '+ '.$dayInterval.' days';
        }

        $options = array_merge($options, $daysOptions);

        $beforeDaysOptions = [];
        for ($dayInterval = 2; $dayInterval <= 31; ++$dayInterval) {
            $beforeDaysOptions['-P'.$dayInterval.'D'] = $dayInterval.' days before';
        }

        $options = array_merge($options, $beforeDaysOptions);

        return $options;
    }
}
