<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests;

class Fixtures
{
    /**
     * @return mixed[]
     */
    public static function getValidReportResult(): array
    {
        return [
            'dateFrom'     => Fixtures::getDateFrom(),
            'dateTo'       => Fixtures::getDateTo(),
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
            'page'  => 1,
            'limit' => 10000,
        ];
    }

    /**
     * @return mixed[]
     */
    public static function getValidReportData(): array
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

    /**
     * @return array<int, string>
     */
    public static function getValidReportHeaders(): array
    {
        return [
            'City',
            'Company',
            'Country',
            'Date identified',
            'Email',
        ];
    }

    public static function getValidReportTotalResult(): int
    {
        return 11;
    }

    public static function getStringType(): string
    {
        return 'string';
    }

    public static function getIntegerType(): string
    {
        return 'int';
    }

    public static function getBooleanType(): string
    {
        return 'bool';
    }

    public static function getFloatType(): string
    {
        return 'float';
    }

    public static function getDateType(): string
    {
        return 'datetime';
    }

    public static function getEmailType(): string
    {
        return 'email';
    }

    /**
     * @return mixed[]
     */
    public static function getReportBuilderEventData(): array
    {
        return [
            'all' => [
                'tables' => [
                    'assets' => [
                        'display_name' => 'mautic.asset.assets',
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
                        'group' => 'assets',
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

    public static function getGoodColumnList(): \stdClass
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

    /**
     * @return mixed[]
     */
    public static function getValidReportResultWithAggregatedColumns(): array
    {
        return [
            'dateFrom'     => Fixtures::getDateFrom(),
            'dateTo'       => Fixtures::getDateTo(),
            'totalResults' => '2',
            'data'         => self::getValidReportDataAggregatedColumns(),
            'dataColumns'  => [
                'e_id'           => 'e.id',
                'e_name'         => 'e.name',
                'SUM es.is_read' => 'es.is_read',
                'AVG es.is_read' => 'es.is_read',
                'COUNT l.id'     => 'l.id',
            ],
            'columns' => [
                'e.id' => [
                    'label' => 'ID',
                    'type'  => self::getIntegerType(),
                    'link'  => 'mautic_email_action',
                    'alias' => 'e_id',
                ],
                'e.name' => [
                    'label' => 'Name',
                    'type'  => self::getStringType(),
                    'alias' => 'e_name',
                ],
                'es.is_read' => [
                    'label' => 'Read',
                    'type'  => self::getBooleanType(),
                    'alias' => 'is_read',
                ],
                'l.id' => [
                    'label' => 'Contact ID',
                    'type'  => self::getIntegerType(),
                    'link'  => 'mautic_email_action',
                    'alias' => 'contactId',
                ],
            ],
            'aggregatorColumns' => [
                'SUM es.is_read' => 'es.is_read',
                'AVG es.is_read' => 'es.is_read',
                'COUNT l.id'     => 'l.id',
            ],
            'limit' => 0,
        ];
    }

    /**
     * @return mixed[]
     */
    public static function getValidReportDataAggregatedColumns(): array
    {
        return [
            [
                'e_id'           => '1',
                'e_name'         => 'Email 1',
                'SUM es.is_read' => '50',
                'AVG es.is_read' => '0.5000',
                'COUNT l.id'     => '100',
            ],
            [
                'e_id'           => '2',
                'e_name'         => 'Email 2',
                'SUM es.is_read' => '10',
                'AVG es.is_read' => '0.1666',
                'COUNT l.id'     => '60',
            ],
        ];
    }

    /**
     * @return array<float>
     */
    public static function getValidReportDataAggregatedTotals(): array
    {
        return [
            'SUM es.is_read' => 60,
            'AVG es.is_read' => 0.3333,
            'COUNT l.id'     => 160,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getValidReportWithAggregatedColumnsHeaders(): array
    {
        return [
            'ID',
            'Name',
            'SUM Read',
            'AVG Read',
            'COUNT Contact ID',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function getValidReportWithAggregatedColumnsKeys(): array
    {
        return [
            'e_id',
            'e_name',
            'SUM es.is_read',
            'AVG es.is_read',
            'COUNT l.id',
        ];
    }

    public static function getValidReportWithAggregatedColumnsTotalResult(): int
    {
        return 2;
    }

    /**
     * @return array<mixed>
     */
    public static function getValidReportResultWithNoGraphs(): array
    {
        $validReportResult           = Fixtures::getValidReportResult();
        $validReportResult['graphs'] = [];

        return $validReportResult;
    }

    /**
     * @return array<mixed>
     */
    public static function getValidReportResultWithGraphs(): array
    {
        return [
            'dateFrom'     => Fixtures::getDateFrom(),
            'dateTo'       => Fixtures::getDateTo(),
            'totalResults' => '2',
            'data'         => [
                [
                    'e_id'           => '1',
                    'e_name'         => 'Test 123',
                    'e_date_added'   => '2023-02-27 10:00:00',
                ],
                [
                    'e_id'           => '2',
                    'e_name'         => 'Test abc',
                    'e_date_added'   => '2023-01-15 9:00:00',
                ],
            ],
            'dataColumns'  => [
                'e_id'         => 'e.id',
                'subject'      => 'vp.subject',
                'bounced'      => 'bounced',
                'e_name'       => 'e.name',
                'e_date_added' => 'e.date_added',
            ],
            'columns' => [
                'e.id' => [
                    'label' => 'ID',
                    'type'  => self::getIntegerType(),
                    'link'  => 'mautic_email_action',
                    'alias' => 'e_id',
                ],
                'e.name' => [
                    'label' => 'Name',
                    'type'  => self::getStringType(),
                    'alias' => 'e_name',
                ],
                'e.date_added' => [
                    'label' => 'Date created',
                    'type'  => 'datetime',
                    'alias' => 'e_date_added',
                ],
            ],
            'page'   => 1,
            'limit'  => 10,
            'graphs' => [
                'mautic.email.graph.line.stats' => [
                    'options'        => [],
                    'dynamicFilters' => [],
                    'paginate'       => true,
                ],
            ],
        ];
    }

    public static function getDateFrom(): \DateTime
    {
        return new \DateTime('2023-02-24 00:00');
    }

    public static function getDateTo(): \DateTime
    {
        return new \DateTime('2023-03-24 00:00');
    }
}
