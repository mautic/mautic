<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests;

class Fixtures
{
    /**
     * @return array
     */
    public static function getValidReportResult()
    {
        return [
            'totalResults' => '11',
            'data'         => self::getValidReportData(),
            'dataColumns'  => [
                'city'            => 'l.city',
                'company'         => 'l.company',
                'country'         => 'l.country',
                'date_identified' => 'l.date_identified',
                'email'           => 'l.email',
            ],
            'columns' => [
                'l.city' => [
                    'label' => 'City',
                    'type'  => self::getStringType(),
                    'alias' => 'city',
                ],
                'l.company' => [
                    'label' => 'Company',
                    'type'  => self::getStringType(),
                    'alias' => 'company',
                ],
                'l.country' => [
                    'label' => 'Country',
                    'type'  => self::getStringType(),
                    'alias' => 'country',
                ],
                'l.date_identified' => [
                    'label'          => 'Date identified',
                    'type'           => self::getDateType(),
                    'groupByFormula' => 'DATE(l.date_identified)',
                    'alias'          => 'date_identified',
                ],
                'l.email' => [
                    'label' => 'Email',
                    'type'  => self::getEmailType(),
                    'alias' => 'email',
                ],
            ],
            'limit' => 10000,
        ];
    }

    /**
     * @return array
     */
    public static function getValidReportData()
    {
        return [
            [
                'city'            => 'City',
                'company'         => '',
                'country'         => '',
                'date_identified' => '',
                'email'           => '',
            ],
            [
                'city'            => '',
                'company'         => 'Company',
                'country'         => '',
                'date_identified' => '',
                'email'           => '',
            ],
            [
                'city'            => '',
                'company'         => '',
                'country'         => 'Country',
                'date_identified' => '',
                'email'           => '',
            ],
            [
                'city'            => '',
                'company'         => 'ConnectWise',
                'country'         => '',
                'date_identified' => '2017-10-10',
                'email'           => 'connectwise@example.com',
            ],
            [
                'city'            => '',
                'company'         => '',
                'country'         => '',
                'date_identified' => '2017-10-10',
                'email'           => 'mytest@example.com',
            ],
            [
                'city'            => '',
                'company'         => '',
                'country'         => '',
                'date_identified' => '2017-10-10',
                'email'           => 'john@example.com',
            ],
            [
                'city'            => '',
                'company'         => '',
                'country'         => '',
                'date_identified' => '2017-10-10',
                'email'           => 'bogus@example.com',
            ],
            [
                'city'            => '',
                'company'         => '',
                'country'         => '',
                'date_identified' => '2017-10-10',
                'email'           => 'date-test@example.com',
            ],
            [
                'city'            => '',
                'company'         => 'Bodega Club',
                'country'         => '',
                'date_identified' => '2017-10-10',
                'email'           => 'club@example.com',
            ],
            [
                'city'            => '',
                'company'         => '',
                'country'         => '',
                'date_identified' => '2017-10-11',
                'email'           => 'test@example.com',
            ],
            [
                'city'            => '',
                'company'         => '',
                'country'         => '',
                'date_identified' => '2017-10-12',
                'email'           => 'test@example.com',
            ],
        ];
    }

    public static function getValidReportHeaders()
    {
        return [
            'City',
            'Company',
            'Country',
            'Date identified',
            'Email',
        ];
    }

    /**
     * @return int
     */
    public static function getValidReportTotalResult()
    {
        return 11;
    }

    /**
     * @return string
     */
    public static function getStringType()
    {
        return 'string';
    }

    /**
     * @return string
     */
    public static function getDateType()
    {
        return 'datetime';
    }

    /**
     * @return string
     */
    public static function getEmailType()
    {
        return 'email';
    }

    public static function getReportBuilderEventData()
    {
        return [
            'all' => [
                'tables' => [
                    'assets' => [
                        'display_name' => 'mautic.assets.assets',
                        'columns'      => [
                            'a.alias' => [
                                'label' => 'Alias',
                                'type'  => 'string',
                                'alias' => 'alias',
                            ],
                            'a.description' => [
                                'label' => 'Description',
                                'type'  => 'string',
                                'alias' => 'a_description',
                            ],
                        ],
                        'groups' => 'assets',
                    ],
                ],
                'graphs' => [
                    'all'                 => [],
                    'mautic.asset.assets' => [
                        'tables' => [],
                    ],
                ],
            ],
        ];
    }

    public static function getGoodColumnList()
    {
        $list          = new \stdClass();
        $list->choices = [
            'a.alias'       => 'Alias',
            'a.description' => 'Description',
        ];
        $list->choiceHtml  = '<option value="a.alias">Alias</option>\n<option value="a.description">Description</option>\n';
        $list->definitions = [
            'a.alias' => [
                'label' => 'Alias',
                'type'  => 'string',
                'alias' => 'alias',
            ],
            'a.description' => [
                'label' => 'Description',
                'type'  => 'string',
                'alias' => 'a_description',
            ],
        ];

        return $list;
    }
}
