<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\PageBundle\Entity\Hit;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoadPageHitData.
 */
class LoadPageHitData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        $repo = $this->container->get('mautic.page.model.page')->getRepository();
        $hits = CsvHelper::csv_to_array(__DIR__.'/fakepagehitdata.csv');

        foreach ($hits as $count => $rows) {
            $hit = new Hit();
            foreach ($rows as $col => $val) {
                if ($val != 'NULL') {
                    $setter = 'set'.ucfirst($col);
                    if (in_array($col, ['page', 'ipAddress'])) {
                        $hit->$setter($this->getReference($col.'-'.$val));
                    } elseif (in_array($col, ['dateHit', 'dateLeft'])) {
                        $hit->$setter(new \DateTime($val));
                    } elseif ($col == 'browserLanguages') {
                        $val = unserialize(stripslashes($val));
                        $hit->$setter($val);
                    } else {
                        $hit->$setter($val);
                    }
                }
            }
            $repo->saveEntity($hit);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 8;
    }
}
