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
use Doctrine\Common\Persistence\ObjectManager;
use Mautic\LeadBundle\Entity\LeadList;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoadSegmentsData.
 */
class LoadSegmentsData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
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
                'manually_add'    => [48, 49, 50],
                'manually_remove' => [3, 4],
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
                ],
                'populate' => true,
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
        $list->setAlias($listConfig['alias']);
        $list->setCreatedBy($adminUser);
        $list->setIsGlobal($listConfig['public']);
        $list->setFilters($listConfig['filters']);

        $this->setReference($listConfig['alias'], $list);

        $manager->persist($list);
        $manager->flush();

        if ($listConfig['populate']) {
            $this->container->get('mautic.lead.model.list')->rebuildListLeads($list);
        }

        if (!empty($listConfig['manually_add'])) {
            foreach ($listConfig['manually_add'] as $lead) {
                $this->container->get('mautic.lead.model.lead')->addToLists($lead, $list);
            }
        }

        if (!empty($listConfig['manually_remove'])) {
            foreach ($listConfig['manually_remove'] as $lead) {
                $this->container->get('mautic.lead.model.lead')->removeFromLists($lead, $list);
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
