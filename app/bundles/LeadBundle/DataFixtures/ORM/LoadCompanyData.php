<?php

namespace Mautic\LeadBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Model\CompanyModel;

class LoadCompanyData extends AbstractFixture implements OrderedFixtureInterface
{
    public function __construct(
        private CompanyModel $companyModel
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $today     = new \DateTime();
        $companies = CsvHelper::csv_to_array(__DIR__.'/fakecompanydata.csv');
        foreach ($companies as $count => $l) {
            $company = new Company();
            $company->setDateAdded($today);
            foreach ($l as $col => $val) {
                $company->addUpdatedField($col, $val);
            }
            $this->companyModel->saveEntity($company);

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
