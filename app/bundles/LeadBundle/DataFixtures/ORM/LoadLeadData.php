<?php

namespace Mautic\LeadBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\CsvHelper;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;

class LoadLeadData extends AbstractFixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var LeadRepository $leadRepo */
        $leadRepo        = $manager->getRepository(Lead::class);

        /** @var CompanyLeadRepository $companyLeadRepo */
        $companyLeadRepo = $manager->getRepository(CompanyLead::class);

        /** @var IpAddressRepository $ipRepo */
        $ipRepo = $manager->getRepository(IpAddress::class);

        $today = new \DateTime();
        $leads = CsvHelper::csv_to_array(__DIR__.'/fakeleaddata.csv');

        // Track IPs created during this loop to avoid duplicates
        // Doctrine\DBAL\Driver\PDO\Exception: SQLSTATE[23505]:
        // Unique violation: 7 ERROR:
        // duplicate key value violates unique constraint "idx_ip_address"
        $createdIps = [];

        foreach ($leads as $count => $l) {
            $key  = $count + 1;
            $lead = new Lead();
            $lead->setDateAdded($today);

            $ipStr = $l['ip'];

            // 1. Check if we already handled this IP in this loop or if it exists in DB
            if (!isset($createdIps[$ipStr])) {
                $ipAddress = $ipRepo->findOneBy(['ip_address' => $ipStr]);

                if (!$ipAddress) {
                    $ipAddress = new IpAddress();
                    $ipAddress->setIpAddress($ipStr);
                    // We must persist/save immediately if using a repository that
                    // doesn't track unit of work, or let the Lead persist handle it.
                    // However, for testing, it's safer to track it:
                }
                $createdIps[$ipStr] = $ipAddress;
                $this->setReference('ipAddress-'.$key, $ipAddress);
            } else {
                $ipAddress = $createdIps[$ipStr];
            }

            unset($l['ip']);

            $lead->addIpAddress($ipAddress);

            if ($this->hasReference('sales-user')) {
                $lead->setOwner($this->getReference('sales-user'));
            }

            foreach ($l as $col => $val) {
                $lead->addUpdatedField($col, $val);
            }

            $leadRepo->saveEntity($lead);

            $this->setReference('lead-'.$count, $lead);

            // Assign to companies in a predictable way
            $lastCharacter = (int) substr($count, -1, 1);
            if ($lastCharacter <= 3) {
                if ($this->hasReference('company-'.$lastCharacter)) {
                    $companyLead = new CompanyLead();
                    $companyLead->setLead($lead);
                    $companyLead->setCompany($this->getReference('company-'.$lastCharacter));
                    $companyLead->setDateAdded($today);
                    $companyLead->setPrimary(true);
                    $companyLeadRepo->saveEntity($companyLead);
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
