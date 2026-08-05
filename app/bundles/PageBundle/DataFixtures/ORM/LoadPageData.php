<?php

namespace Mautic\PageBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\CoreBundle\Helper\Serializer;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Entity\PageRepository;

final class LoadPageData extends AbstractFixture implements OrderedFixtureInterface
{
    public function __construct(
        private readonly PageRepository $pageRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $pages = CsvHelper::csv_to_array(__DIR__.'/fakepagedata.csv');
        foreach ($pages as $count => $rows) {
            $page = new Page();
            $key  = $count + 1;
            foreach ($rows as $col => $val) {
                if ('NULL' != $val) {
                    $setter = 'set'.ucfirst($col);
                    if (in_array($col, ['translationParent', 'variantParent'])) {
                        $page->{$setter}($this->getReference('page-'.$val));
                    } elseif (in_array($col, ['dateAdded', 'variantStartDate'])) {
                        $page->{$setter}(new \DateTime($val));
                    } elseif (in_array($col, ['content', 'variantSettings'])) {
                        $val = Serializer::decode(stripslashes($val));
                        $page->{$setter}($val);
                    } else {
                        $page->{$setter}($val);
                    }
                }
            }
            $page->setCategory($this->getReference('page-cat-1'));
            $this->pageRepository->saveEntity($page);

            $this->setReference('page-'.$key, $page);
        }
    }

    public function getOrder(): int
    {
        return 7;
    }
}
