<?php

namespace Mautic\LeadBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CategoryBundle\Entity\CategoryRepository;
use Mautic\CoreBundle\Helper\CsvHelper;

class LoadCategoryData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var CategoryRepository $categoryRepo */
        $categoryRepo = $manager->getRepository(Category::class);
        $categories   = CsvHelper::csv_to_array(__DIR__.'/fakecategorydata.csv');
        foreach ($categories as $category) {
            $categoryEntity = new Category();
            $categoryEntity->setTitle($category['categoryname']);
            $categoryEntity->setBundle($category['categorybundle']);
            $categoryEntity->setAlias($category['categoryalias']);
            $categoryEntity->setIsPublished($category['published']);
            $categoryRepo->saveEntity($categoryEntity);
        }
    }

    public function getOrder(): int
    {
        return 1;
    }
}
