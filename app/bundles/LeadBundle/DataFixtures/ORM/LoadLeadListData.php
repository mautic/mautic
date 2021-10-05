<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\ListModel;

class LoadLeadListData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @var ListModel
     */
    private $segmentModel;

    /**
     * {@inheritdoc}
     */
    public function __construct(ListModel $segmentModel)
    {
        $this->segmentModel = $segmentModel;
    }

    public function load(ObjectManager $manager)
    {
        $adminUser = $this->getReference('admin-user');

        $list = new LeadList();
        $list->setName('United States');
        $list->setPublicName('United States');
        $list->setAlias('us');
        $list->setCreatedBy($adminUser);
        $list->setIsGlobal(true);
        $list->setFilters([
            [
                'glue'     => 'and',
                'type'     => 'lookup',
                'field'    => 'country',
                'operator' => '=',
                'filter'   => 'United States',
                'display'  => '',
            ],
        ]);

        $this->setReference('lead-list', $list);
        $manager->persist($list);
        $manager->flush();

        $this->segmentModel->rebuildListLeads($list);
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 5;
    }
}
