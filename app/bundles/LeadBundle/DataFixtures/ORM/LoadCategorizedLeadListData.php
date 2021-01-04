<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CategoryBundle\Entity\CategoryRepository;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;

class LoadCategorizedLeadListData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Category
     */

    /**
     * {@inheritdoc}
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function load(ObjectManager $manager)
    {
        /** @var LeadListRepository $categoryRepo */
        $leadListRepo = $this->entityManager->getRepository(LeadList::class);
        /** @var CategoryRepository $categoryRepo */
        $categoryRepo = $this->entityManager->getRepository(Category::class);

        $leadLists = CsvHelper::csv_to_array(__DIR__.'/fakecategorizedleadlistdata.csv');
        foreach ($leadLists as $leadList) {
            $category       = $categoryRepo->find($leadList['category']);
            $leadListEntity = new LeadList();
            $leadListEntity->setName($leadList['name']);
            $leadListEntity->setPublicName($leadList['publicname']);
            $leadListEntity->setAlias($leadList['alias']);
            $leadListEntity->setCategory($category);
            $leadListRepo->saveEntity($leadListEntity);
        }
    }

    public function getOrder()
    {
        // TODO: Implement getOrder() method.
    }
}
