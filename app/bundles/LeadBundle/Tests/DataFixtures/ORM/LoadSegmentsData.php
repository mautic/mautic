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
            [
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
            [
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
            [
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
            [
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
            [
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
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 7;
    }
}
