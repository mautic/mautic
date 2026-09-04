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
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;

final class LoadLeadData extends AbstractFixture implements OrderedFixtureInterface
{
    public function __construct(
        private readonly LeadRepository $leadRepository,
        private readonly CompanyLeadRepository $companyLeadRepository,
    ) {
    }

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

            if ($this->hasReference('sales-user')) {
                $lead->setOwner($this->getReference('sales-user'));
            }

            foreach ($l as $col => $val) {
                $lead->addUpdatedField($col, $val);
            }

            $this->leadRepository->saveEntity($lead);

            $this->setReference('lead-'.$count, $lead);

            // Assign to companies in a predictable way
            $lastCharacter = (int) substr((string) $count, -1, 1);
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
                    $this->companyLeadRepository->saveEntity($companyLead);
                }
            }
        }
    }

    public function getOrder(): int
    {
        return 5;
    }
}
