<?php

namespace Mautic\LeadBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\UserBundle\Entity\User;

class LoadLeadData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $today = new \DateTime();
        $leads = CsvHelper::csv_to_array(__DIR__.'/fakeleaddata.csv');

        foreach ($leads as $count => $l) {
            $key  = $count + 1;
            $lead = new Lead();
            $lead->setDateAdded($today);
            $ipAddress = new IpAddress();
            $ipAddress->setIpAddress($l['ip']);
            $this->setReference('ipAddress-'.$key, $ipAddress);
            unset($l['ip']);
            $lead->addIpAddress($ipAddress);

            $salesUser = $manager->getRepository(User::class)->findOneBy(['username' => 'sales']);
            if ($salesUser instanceof User) {
                $lead->setOwner($salesUser);
            }

            foreach ($l as $col => $val) {
                $lead->addUpdatedField($col, $val);
            }

            $manager->persist($lead);
            $manager->flush();

            $this->setReference('lead-'.$count, $lead);

            // Assign to companies in a predictable way
            $lastCharacter = (int) substr($count, -1, 1);
            if ($lastCharacter <= 3) {
                if ($this->hasReference('company-'.$lastCharacter)) {
                    $companyLead = new CompanyLead();
                    $company     = $this->getReference('company-'.$lastCharacter);
                    \assert($company instanceof Company);
                    \assert($manager instanceof EntityManagerInterface);
                    $managedCompany = $manager->getReference(Company::class, $company->getId());
                    \assert($managedCompany instanceof Company);
                    $companyLead->setLead($lead);
                    $companyLead->setCompany($managedCompany);
                    $companyLead->setDateAdded($today);
                    $companyLead->setPrimary(true);
                    $manager->persist($companyLead);
                    $manager->flush();
                }
            }
        }
    }

    /**
     * @return int
     */
    public function getOrder()
    {
        return 5;
    }
}
