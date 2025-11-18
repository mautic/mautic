<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CategoryBundle\Entity\Category;
use Mautic\EmailBundle\Entity\Email;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadCategory;
use Mautic\LeadBundle\Entity\LeadEventLog;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\UserBundle\Entity\User;

trait CreateTestEntitiesTrait
{
    private function createLead(string $firstName, string $lastName = '', string $emailId = '', User $createdBy = null): Lead
    {
        $lead = new Lead();
        $lead->setFirstname($firstName);

        if ($lastName) {
            $lead->setLastname($lastName);
        }

        if ($emailId) {
            $lead->setEmail($emailId);
        }

        if ($createdBy) {
            $lead->setCreatedBy($createdBy);
        }

        $this->em->persist($lead);

        return $lead;
    }

    private function createCampaign(string $campaignName): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName($campaignName);
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);

        return $campaign;
    }

    private function createPrimaryCompanyForLead(Lead $lead, Company $company, bool $isPrimary = true): CompanyLead
    {
        $companyLead = new CompanyLead();
        $companyLead->setCompany($company);
        $companyLead->setLead($lead);
        $companyLead->setDateAdded(new \DateTime());
        $companyLead->setPrimary($isPrimary);
        $this->em->persist($companyLead);

        return $companyLead;
    }

    /**
     * @param mixed[] $properties
     */
    private function createEvent(string $name, Campaign $campaign, string $type, string $eventType, array $properties = []): Event
    {
        $event = new Event();
        $event->setName($name);
        $event->setCampaign($campaign);
        $event->setType($type);
        $event->setEventType($eventType);
        $event->setTriggerInterval(1);
        $event->setTriggerMode('immediate');

        if ($properties) {
            $event->setProperties($properties);
        }

        $this->em->persist($event);

        return $event;
    }

    /**
     * @param mixed[] $filters
     */
    private function createSegment(string $alias, array $filters): LeadList
    {
        $segment = new LeadList();
        $segment->setAlias($alias);
        $segment->setName($alias);
        $segment->setPublicName($alias);
        $segment->setFilters($filters);
        $this->em->persist($segment);

        return $segment;
    }

    private function createCategory(string $name, string $alias, string $bundle = 'global'): Category
    {
        $category = new Category();
        $category->setTitle($name);
        $category->setAlias($alias);
        $category->setBundle($bundle);

        $this->em->persist($category);

        return $category;
    }

    private function createLeadCategory(Lead $lead, Category $category, bool $flag): void
    {
        $leadCategory = new LeadCategory();
        $leadCategory->setLead($lead);
        $leadCategory->setCategory($category);
        $leadCategory->setDateAdded(new \DateTime());
        $leadCategory->setManuallyAdded($flag);
        $leadCategory->setManuallyRemoved(!$flag);

        $this->em->persist($leadCategory);
    }

    private function createEmail(string $name): Email
    {
        $email = new Email();
        $email->setName($name);
        $email->setSubject('Test Subject');
        $email->setIsPublished(true);

        $this->em->persist($email);

        return $email;
    }

    private function createCompany(string $name, string $email): Company
    {
        $company = new Company();
        $company->setName($name);
        $company->setEmail($email);

        $this->em->persist($company);

        return $company;
    }

    private function createListLead(LeadList $segment, Lead $lead): void
    {
        $listLead = new ListLead();
        $listLead->setLead($lead);
        $listLead->setList($segment);
        $listLead->setDateAdded(new \DateTime());

        $this->em->persist($listLead);
    }

    /**
     * @param array<mixed> $properties
     */
    private function createLeadEventLogEntry(Lead $lead, string $bundle, string $object, string $action, int $objectId, array $properties = []): LeadEventLog
    {
        $listEventLog = new LeadEventLog();
        $listEventLog->setLead($lead);
        $listEventLog->setBundle($bundle);
        $listEventLog->setObject($object);
        $listEventLog->setAction($action);
        $listEventLog->setObjectId($objectId);
        $listEventLog->setProperties($properties);
        $listEventLog->setDateAdded(new \DateTime());

        $this->em->persist($listEventLog);

        return $listEventLog;
    }
}
