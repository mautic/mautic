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
use Doctrine\Common\Persistence\ObjectManager;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LoadCompanyData.
 */
class LoadCompanyData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
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
        $repo     = $this->container->get('mautic.lead.model.company')->getRepository();
        $today    = new \DateTime();

        $companies = CsvHelper::csv_to_array(__DIR__.'/fakecompanydata.csv');
        foreach ($companies as $count => $l) {
            $company = new Company();
            $company->setDateAdded($today);
            foreach ($l as $col => $val) {
                $company->addUpdatedField($col, $val);
            }
            $repo->saveEntity($company);

            $this->setReference('company-'.$count, $company);
        }
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 4;
    }
}
