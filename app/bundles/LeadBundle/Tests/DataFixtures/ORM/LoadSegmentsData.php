<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;

class LoadSegmentsData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @var ListModel
     */
    private $listModel;

    /**
     * @var LeadModel
     */
    private $contactModel;

    /**
     * {@inheritdoc}
     */
    public function __construct(ListModel $listModel, LeadModel $contactModel)
    {
        $this->listModel    = $listModel;
        $this->contactModel = $contactModel;
    }

    public function load(ObjectManager $manager)
    {
        $segments = [
            [ // ID 2
                'name'    => 'Segment Test 1',
                'alias'   => 'segment-test-1',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'lookup',
                        'field'    => 'state',
                        'operator' => '=',
                        'filter'   => 'IA',
                        'display'  => '',
                    ],
                ],
                'populate' => true,
            ],
            [ // ID 3
                'name'    => 'Segment Test 2',
                'alias'   => 'segment-test-2',
                'public'  => false,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'lookup',
                        'field'    => 'state',
                        'operator' => '=',
                        'filter'   => 'IA',
                        'display'  => '',
                    ],
                    [
                        'glue'     => 'or',
                        'type'     => 'lookup',
                        'field'    => 'state',
                        'operator' => '=',
                        'filter'   => 'QLD',
                        'display'  => '',
                    ],
                ],
                'populate' => true,
            ],
            [ // ID 4
                'name'    => 'Segment Test 3',
                'alias'   => 'segment-test-3',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'lookup',
                        'field'    => 'title',
                        'operator' => '=',
                        'filter'   => 'Mr.',
                        'display'  => '',
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 5
                'name'    => 'Segment Test 4',
                'alias'   => 'segment-test-4',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'field'    => 'hit_url',
                        'operator' => 'like',
                        'filter'   => 'test.com',
                        'display'  => '',
                    ],
                ],
                'populate' => true,
            ],
            [ // ID 6
                'name'    => 'Segment Test 5',
                'alias'   => 'segment-test-5',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'field'    => 'hit_url',
                        'operator' => '!like',
                        'filter'   => 'test.com',
                        'display'  => '',
                    ],
                ],
                'populate' => true,
            ],
            [ // ID 7
                'name'    => 'Like segment test with field percent sign at end',
                'alias'   => 'like-percent-end',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'field'    => 'title',
                        'operator' => 'like',
                        'filter'   => 'Mr%',
                        'display'  => '',
                    ],
                ],
                'populate' => true,
            ],
            [ // ID 8
                'name'     => 'Segment without filters',
                'alias'    => 'segment-test-without-filters',
                'public'   => true,
                'filters'  => [],
                'populate' => true,
            ],
            [ // ID 9
                'name'    => 'Segment with manual members added and removed',
                'alias'   => 'segment-test-manual-membership',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'field'    => 'country',
                        'operator' => '=',
                        'filter'   => 'United Kingdom',
                        'display'  => '',
                    ],
                ],
                'populate'        => true,
                'manually_add'    => [$this->getReference('lead-1')->id, $this->getReference('lead-2')->id, $this->getReference('lead-3')->id],
                'manually_remove' => [$this->getReference('lead-4')->id, $this->getReference('lead-5')->id],
            ],
            [ // ID 10
                'name'    => 'Include segment membership with filters',
                'alias'   => 'segment-test-include-segment-with-filters',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'leadlist',
                        'field'    => 'leadlist',
                        'operator' => 'in',
                        'filter'   => [3, 4],
                        'display'  => '',
                    ],
                ],
                'populate' => true,
            ],
            [ // ID 11
                'name'    => 'Exclude segment membership with filters',
                'alias'   => 'segment-test-exclude-segment-with-filters',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'field'    => 'country',
                        'operator' => '=',
                        'filter'   => 'United States',
                        'display'  => '',
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'leadlist',
                        'field'    => 'leadlist',
                        'operator' => '!in',
                        'filter'   => [3],
                        'display'  => '',
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 12
                'name'    => 'Include segment membership without filters',
                'alias'   => 'segment-test-include-segment-without-filters',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'field'    => 'country',
                        'operator' => '=',
                        'filter'   => 'United Kingdom',
                        'display'  => '',
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'leadlist',
                        'field'    => 'leadlist',
                        'operator' => 'in',
                        'filter'   => [8],
                        'display'  => '',
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 13
                'name'    => 'Exclude segment membership without filters',
                'alias'   => 'segment-test-exclude-segment-without-filters',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'field'    => 'country',
                        'operator' => '=',
                        'filter'   => 'United Kingdom',
                        'display'  => '',
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'leadlist',
                        'field'    => 'leadlist',
                        'operator' => '!in',
                        'filter'   => [8],
                        'display'  => '',
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 14
                'name'    => 'Include segment membership with mixed filters',
                'alias'   => 'segment-test-include-segment-mixed-filters',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'leadlist',
                        'field'    => 'leadlist',
                        'operator' => 'in',
                        'filter'   => [4, 8],
                        'display'  => '',
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 15
                'name'    => 'Exclude segment membership with mixed filters',
                'alias'   => 'segment-test-exclude-segment-mixed-filters',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'leadlist',
                        'field'    => 'leadlist',
                        'operator' => '!in',
                        'filter'   => [4, 8],
                        'display'  => '',
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 16
                'name'    => 'Segment membership with mixed include and exclude',
                'alias'   => 'segment-test-mixed-include-exclude-filters',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'leadlist',
                        'field'    => 'leadlist',
                        'operator' => 'in',
                        'filter'   => [7],
                        'display'  => '',
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'leadlist',
                        'field'    => 'leadlist',
                        'operator' => '!in',
                        'filter'   => [4],
                        'display'  => '',
                    ],
                ],
                'populate' => true,
            ],
            [ // ID 17
                'name'    => 'Segment membership with including segment that has manual membership',
                'alias'   => 'segment-test-include-segment-manual-members',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'leadlist',
                        'field'    => 'leadlist',
                        'operator' => 'in',
                        'filter'   => [9],
                        'display'  => '',
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 18
                'name'    => 'Segment membership with excluded segment that has manual membership',
                'alias'   => 'segment-test-exclude-segment-manual-members',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'field'    => 'title',
                        'operator' => 'like',
                        'filter'   => 'Mr%',
                        'display'  => '',
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'leadlist',
                        'field'    => 'leadlist',
                        'operator' => '!in',
                        'filter'   => [9],
                        'display'  => '',
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 19
                'name'    => 'Segment membership with excluded segment without other filters',
                'alias'   => 'segment-test-exclude-segment-without-other-filters',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'leadlist',
                        'field'    => 'leadlist',
                        'operator' => '!in',
                        'filter'   => [9],
                        'display'  => '',
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 20
                'name'    => 'Segment with filters and only manually removed contacts',
                'alias'   => 'segment-test-filters-and-removed',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'field'    => 'country',
                        'operator' => '=',
                        'filter'   => 'United Kingdom',
                        'display'  => '',
                    ],
                ],
                'populate'        => true,
                'manually_remove' => [3, 4],
            ],
            [ // ID 21
                'name'    => 'Segment with same filters as another that has manually removed contacts',
                'alias'   => 'segment-test-include-segment-with-segment-manual-removal-same-filters',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'field'    => 'country',
                        'operator' => '=',
                        'filter'   => 'United Kingdom',
                        'display'  => '',
                    ],
                ],
                'populate' => true,
            ],
            [ // ID 22
                'name'    => 'Segment membership with including segment that has a contact thats been removed from non-related segment',
                'alias'   => 'segment-test-include-segment-with-unrelated-segment-manual-removal',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'leadlist',
                        'field'    => 'leadlist',
                        'operator' => 'in',
                        'filter'   => [21],
                        'display'  => '',
                    ],
                ],
                'populate' => true,
            ],
            [ // ID 23
                'name'    => 'Segment membership based on regex with special characters',
                'alias'   => 'segment-membership-regexp',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'field'    => 'email',
                        'operator' => 'regexp',
                        'filter'   => '^.*(#|!|\\\\$|%|&|\\\\*|\\\\(|\\\\)|\\\\^|\\\\?|\\\\+|-|dayrep\\\\.com|http|gmail|abc|qwe|[0-9]).*$',
                        'display'  => '',
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'field'    => 'email',
                        'operator' => '!empty',
                        'filter'   => null,
                        'display'  => '',
                    ],
                ],
                'populate' => true,
            ],
            [ // ID 24
                'name'    => 'Segment membership based on only company fields',
                'alias'   => 'segment-company-only-fields',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'object'   => 'company',
                        'field'    => 'companycity',
                        'operator' => '=',
                        'filter'   => 'Houston',
                        'display'  => '',
                    ],
                ],
                'populate' => true,
            ],
            [ // ID 25
                'name'    => 'Segment membership with excluded segment without other filters',
                'alias'   => 'segment-including-segment-with-company-only-fields',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'object'   => 'company',
                        'field'    => 'companyindustry',
                        'operator' => 'in',
                        'filter'   => ['Software', 'Hardware'],
                        'display'  => '',
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'leadlist',
                        'field'    => 'leadlist',
                        'operator' => '!in',
                        'filter'   => [21],
                        'display'  => '',
                    ],
                ],
                'populate' => true,
            ],
            [ // ID 26
                'name'    => 'Segment with relative date - today',
                'alias'   => 'segment-with-relative-date-today',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'datetime',
                        'object'   => 'lead',
                        'field'    => 'date_identified',
                        'operator' => '=',
                        'filter'   => 'today',
                        'display'  => null,
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'object'   => 'lead',
                        'field'    => 'lastname',
                        'operator' => '=',
                        'filter'   => 'Date',
                        'display'  => null,
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 27
                'name'    => 'Segment with relative date - tomorrow',
                'alias'   => 'segment-with-relative-date-tomorrow',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'datetime',
                        'object'   => 'lead',
                        'field'    => 'date_identified',
                        'operator' => '=',
                        'filter'   => 'tomorrow',
                        'display'  => null,
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'object'   => 'lead',
                        'field'    => 'lastname',
                        'operator' => '=',
                        'filter'   => 'Date',
                        'display'  => null,
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 28
                'name'    => 'Segment with relative date - yesterday',
                'alias'   => 'segment-with-relative-date-yesterday',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'datetime',
                        'object'   => 'lead',
                        'field'    => 'date_identified',
                        'operator' => '=',
                        'filter'   => 'yesterday',
                        'display'  => null,
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'object'   => 'lead',
                        'field'    => 'lastname',
                        'operator' => '=',
                        'filter'   => 'Date',
                        'display'  => null,
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 29
                'name'    => 'Segment with relative date - last week',
                'alias'   => 'segment-with-relative-date-last-week',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'datetime',
                        'object'   => 'lead',
                        'field'    => 'date_identified',
                        'operator' => '=',
                        'filter'   => 'last week',
                        'display'  => null,
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'object'   => 'lead',
                        'field'    => 'lastname',
                        'operator' => '=',
                        'filter'   => 'Date',
                        'display'  => null,
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 30
                'name'    => 'Segment with relative date - next week',
                'alias'   => 'segment-with-relative-date-next-week',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'datetime',
                        'object'   => 'lead',
                        'field'    => 'date_identified',
                        'operator' => '=',
                        'filter'   => 'next week',
                        'display'  => null,
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'object'   => 'lead',
                        'field'    => 'lastname',
                        'operator' => '=',
                        'filter'   => 'Date',
                        'display'  => null,
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 31
                'name'    => 'Segment with relative date - this week',
                'alias'   => 'segment-with-relative-date-this-week',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'datetime',
                        'object'   => 'lead',
                        'field'    => 'date_identified',
                        'operator' => '=',
                        'filter'   => 'this week',
                        'display'  => null,
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'object'   => 'lead',
                        'field'    => 'lastname',
                        'operator' => '=',
                        'filter'   => 'Date',
                        'display'  => null,
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 32
                'name'    => 'Segment with relative date - last month',
                'alias'   => 'segment-with-relative-date-last-month',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'datetime',
                        'object'   => 'lead',
                        'field'    => 'date_identified',
                        'operator' => '=',
                        'filter'   => 'last month',
                        'display'  => null,
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'object'   => 'lead',
                        'field'    => 'lastname',
                        'operator' => '=',
                        'filter'   => 'Date',
                        'display'  => null,
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 33
                'name'    => 'Segment with relative date - next month',
                'alias'   => 'segment-with-relative-date-next-month',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'datetime',
                        'object'   => 'lead',
                        'field'    => 'date_identified',
                        'operator' => '=',
                        'filter'   => 'next month',
                        'display'  => null,
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'object'   => 'lead',
                        'field'    => 'lastname',
                        'operator' => '=',
                        'filter'   => 'Date',
                        'display'  => null,
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 34
                'name'    => 'Segment with relative date - this month',
                'alias'   => 'segment-with-relative-date-this-month',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'datetime',
                        'object'   => 'lead',
                        'field'    => 'date_identified',
                        'operator' => '=',
                        'filter'   => 'this month',
                        'display'  => null,
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'object'   => 'lead',
                        'field'    => 'lastname',
                        'operator' => '=',
                        'filter'   => 'Date',
                        'display'  => null,
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 35
                'name'    => 'Segment with relative date - last year',
                'alias'   => 'segment-with-relative-date-last-year',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'datetime',
                        'object'   => 'lead',
                        'field'    => 'date_identified',
                        'operator' => '=',
                        'filter'   => 'last year',
                        'display'  => null,
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'object'   => 'lead',
                        'field'    => 'lastname',
                        'operator' => '=',
                        'filter'   => 'Date',
                        'display'  => null,
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 36
                'name'    => 'Segment with relative date - next year',
                'alias'   => 'segment-with-relative-date-next-year',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'datetime',
                        'object'   => 'lead',
                        'field'    => 'date_identified',
                        'operator' => '=',
                        'filter'   => 'next year',
                        'display'  => null,
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'object'   => 'lead',
                        'field'    => 'lastname',
                        'operator' => '=',
                        'filter'   => 'Date',
                        'display'  => null,
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 37
                'name'    => 'Segment with relative date - relative plus',
                'alias'   => 'segment-with-relative-date-relative-plus',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'datetime',
                        'object'   => 'lead',
                        'field'    => 'date_identified',
                        'operator' => '=',
                        'filter'   => '+5 days',
                        'display'  => null,
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'object'   => 'lead',
                        'field'    => 'lastname',
                        'operator' => '=',
                        'filter'   => 'Date',
                        'display'  => null,
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 38
                'name'    => 'Segment with relative date - relative minus',
                'alias'   => 'segment-with-relative-date-relative-minus',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'datetime',
                        'object'   => 'lead',
                        'field'    => 'date_identified',
                        'operator' => '=',
                        'filter'   => '-4 days',
                        'display'  => null,
                    ],
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'object'   => 'lead',
                        'field'    => 'lastname',
                        'operator' => '=',
                        'filter'   => 'Date',
                        'display'  => null,
                    ],
                ],
                'populate' => false,
            ],
            [ // ID 39
                'name'    => 'Name is not equal (not null test)',
                'alias'   => 'name-is-not-equal-not-null-test',
                'public'  => true,
                'filters' => [
                    [
                        'glue'     => 'and',
                        'type'     => 'text',
                        'object'   => 'lead',
                        'field'    => 'firstname',
                        'operator' => '!=',
                        'filter'   => 'xxxxx',
                        'display'  => null,
                    ],
                ],
                'populate' => false,
            ],
        ];

        foreach ($segments as $segmentConfig) {
            $this->createSegment($segmentConfig, $manager);
        }
    }

    protected function createSegment($listConfig, ObjectManager $manager)
    {
        $adminUser = $this->getReference('admin-user');

        $list = new LeadList();
        $list->setName($listConfig['name']);
        $list->setPublicName($listConfig['name']);
        $list->setAlias($listConfig['alias']);
        $list->setCreatedBy($adminUser);
        $list->setIsGlobal($listConfig['public']);
        $list->setFilters($listConfig['filters']);

        $this->setReference($listConfig['alias'], $list);

        $manager->persist($list);
        $manager->flush();

        if ($listConfig['populate']) {
            $this->listModel->rebuildListLeads($list);
        }

        if (!empty($listConfig['manually_add'])) {
            foreach ($listConfig['manually_add'] as $lead) {
                $this->contactModel->addToLists($lead, $list);
            }
        }

        if (!empty($listConfig['manually_remove'])) {
            foreach ($listConfig['manually_remove'] as $lead) {
                $this->contactModel->removeFromLists($lead, $list);
            }
        }
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 7;
    }
}
