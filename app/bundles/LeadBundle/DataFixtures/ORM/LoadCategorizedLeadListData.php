<?php

namespace Mautic\LeadBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CategoryBundle\Entity\CategoryRepository;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;

class LoadCategorizedLeadListData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var LeadListRepository $leadListRepo */
        $leadListRepo = $manager->getRepository(LeadList::class);
        /** @var CategoryRepository $categoryRepo */
        $categoryRepo = $manager->getRepository(Category::class);

        $leadLists = CsvHelper::csv_to_array(__DIR__.'/fakecategorizedleadlistdata.csv');
        foreach ($leadLists as $leadList) {
            // PostgreSQL will crash if you pass "" to find() for an integer column.
            $categoryId = !empty($leadList['category']) ? $leadList['category'] : null;

            $category = null;
            if (null !== $categoryId) {
                $category = $categoryRepo->find($categoryId);
            }

            $leadListEntity = new LeadList();
            $leadListEntity->setName($leadList['name']);
            $leadListEntity->setPublicName($leadList['publicname']);
            $leadListEntity->setAlias($leadList['alias']);
            $leadListEntity->setCategory($category);
            $leadListRepo->saveEntity($leadListEntity);
        }
    }

    public function getOrder(): int
    {
        return 1;
    }
}
