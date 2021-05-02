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
use Doctrine\Persistence\ObjectManager;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Model\CompanyModel;

class LoadCompanyData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * @var CompanyModel
     */
    private $companyModel;

    /**
     * {@inheritdoc}
     */
    public function __construct(CompanyModel $companyModel)
    {
        $this->companyModel = $companyModel;
    }

    public function load(ObjectManager $manager)
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
