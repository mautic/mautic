<?php

namespace Mautic\LeadBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\ListModel;

class LoadLeadListData extends AbstractFixture implements OrderedFixtureInterface
{
    public function __construct(
        private ListModel $segmentModel,
    ) {
    }

    public function load(ObjectManager $manager): void
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
