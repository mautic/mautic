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

class Fixures
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
                'email'           => 'help@connectwise.com',
            ],
            [
                'city'            => '',
                'company'         => '',
                'country'         => '',
                'date_identified' => '2017-10-10',
                'email'           => 'mytest@hugedomains.com',
            ],
            [
                'city'            => '',
                'company'         => '',
                'country'         => '',
                'date_identified' => '2017-10-10',
                'email'           => 'john@test.com',
            ],
            [
                'city'            => '',
                'company'         => '',
                'country'         => '',
                'date_identified' => '2017-10-10',
                'email'           => 'werner.garcia@gmail.com',
            ],
            [
                'city'            => '',
                'company'         => '',
                'country'         => '',
                'date_identified' => '2017-10-10',
                'email'           => 'wgarcia@foliomatic.net',
            ],
            [
                'city'            => '',
                'company'         => 'Bodega Club',
                'country'         => '',
                'date_identified' => '2017-10-10',
                'email'           => 'petr.fidler@mautic.com',
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
}
