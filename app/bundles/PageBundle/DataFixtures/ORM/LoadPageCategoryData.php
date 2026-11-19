<?php

namespace Mautic\PageBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\CategoryBundle\Entity\CategoryRepository;

final class LoadPageCategoryData extends AbstractFixture implements OrderedFixtureInterface
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $today  = new \DateTime();
        $cat    = new Category();
        $events = 'Events';

        $cat->setBundle('page');
        $cat->setDateAdded($today);
        $cat->setTitle($events);
        $cat->setAlias(strtolower($events));

        $this->categoryRepository->saveEntity($cat);
        $this->setReference('page-cat-1', $cat);
    }

    public function getOrder(): int
    {
        return 6;
    }
}
