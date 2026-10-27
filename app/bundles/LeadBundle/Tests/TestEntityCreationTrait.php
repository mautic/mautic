<?php

namespace Mautic\LeadBundle\Tests;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;

trait TestEntityCreationTrait
{
    private function createContact(): Lead
    {
        $contact = new Lead();
        $contact->setEmail(sprintf('testmail%s@nomail.com', random_int(1, 10000)));
        $contact->setDateIdentified(new \DateTime());
        $this->em->persist($contact);
        $this->em->flush();

        return $contact;
    }

    private function createCompany(): Company
    {
        $company = new Company();
        $company->setName('Company '.random_int(0, 100000));
        $this->em->persist($company);
        $this->em->flush();

        return $company;
    }

    private function attachContactToCompany(Lead $contact, Company $company, bool $isPrimary = false): void
    {
        $companyLead = new CompanyLead();
        $companyLead->setLead($contact);
        $companyLead->setCompany($company);
        $companyLead->setPrimary($isPrimary);
        $companyLead->setDateAdded(new \DateTime());
        $this->em->persist($companyLead);

        if ($isPrimary) {
            $contact->setCompany($company->getName());
            $this->em->persist($contact);
        }

        $this->em->flush();
    }

    private function updateCompanyName(Company $company): void
    {
        $company->setName($company->getName().random_int(1, 100));
        $this->em->persist($company);
        $this->em->flush();
    }

    private function softDeleteCompany(Company $company): void
    {
        $company->setDeleted(new \DateTime());
        $this->em->persist($company);
        $this->em->flush();
    }
}
