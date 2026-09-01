<?php

namespace Mautic\PageBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\CoreBundle\Helper\Serializer;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\PageRepository;

final class LoadPageHitData extends AbstractFixture implements OrderedFixtureInterface
{
    public function __construct(
        private readonly PageRepository $pageRepository,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $hits = CsvHelper::csv_to_array(__DIR__.'/fakepagehitdata.csv');

        foreach ($hits as $rows) {
            $hit = new Hit();
            foreach ($rows as $col => $val) {
                if ('NULL' != $val) {
                    $setter = 'set'.ucfirst($col);
                    if (in_array($col, ['page', 'ipAddress'])) {
                        $hit->{$setter}($this->getReference($col.'-'.$val));
                    } elseif (in_array($col, ['dateHit', 'dateLeft'])) {
                        $hit->{$setter}(new \DateTime($val));
                    } elseif ('browserLanguages' == $col) {
                        $val = Serializer::decode(stripslashes($val));
                        $hit->{$setter}($val);
                    } else {
                        $hit->{$setter}($val);
                    }
                }
            }
            $this->pageRepository->saveEntity($hit);
        }
    }

    public function getOrder(): int
    {
        return 8;
    }
}
