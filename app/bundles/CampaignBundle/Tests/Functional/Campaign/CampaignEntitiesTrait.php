<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Campaign;

use Doctrine\ORM\Exception\ORMException;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;

trait CampaignEntitiesTrait
{
    /**
     * @param array<mixed> $fieldDetails
     */
    private function makeField(array $fieldDetails): void
    {
        $field = new LeadField();
        $field->setLabel($fieldDetails['alias']);
        $field->setType($fieldDetails['type']);
        $field->setObject($fieldDetails['object'] ?? 'lead');
        $field->setGroup($fieldDetails['group'] ?? 'core');
        $field->setAlias($fieldDetails['alias']);
        $field->setProperties($fieldDetails['properties']);

        $fieldModel = self::getContainer()->get('mautic.lead.model.field');
        \assert($fieldModel instanceof FieldModel);
        $fieldModel->saveEntity($field);
    }

    /**
     * @param array<mixed> $filters
     *
     * @throws ORMException
     */
    protected function createSegment(string $alias, array $filters): LeadList
    {
        $segment = new LeadList();
        $segment->setAlias($alias);
        $segment->setPublicName($alias);
        $segment->setName($alias);
        $segment->setFilters($filters);
        $this->em->persist($segment);

        return $segment;
    }

    /**
     * @param array<mixed> $fieldDetails
     * @param array<mixed> $additionalValue
     */
    private function createLeadData(
        LeadList $segment,
        string $object,
        array $fieldDetails,
        array $additionalValue,
        int $index,
    ): Lead {
        $fieldValue      = !empty($fieldDetails) ?
            array_merge($fieldDetails, ['value' => array_merge(['v'.$index], $additionalValue)]) : [];
        $leadFieldValue  = 'lead' === $object ? $fieldValue : [];
        $lead            = $this->createLead('l'.$index, $leadFieldValue);
        if ('company' === $object) {
            $company = $this->createCompany('c'.$index, $fieldValue);
            $this->createCompanyLeadRelation($company, $lead);
        }
        $this->createSegmentMember($segment, $lead);

        return $lead;
    }

    /**
     * @param array<mixed> $customField
     */
    protected function createLead(string $leadName, array $customField = []): Lead
    {
        $contactRepo = $this->em->getRepository(Lead::class);
        \assert($contactRepo instanceof LeadRepository);
        $lead        = new Lead();
        $lead->setFirstname($leadName);
        if (!empty($customField)) {
            $lead->setFields([
                $customField['group'] => [
                    $customField['alias'] => [
                        'value' => '',
                        'alias' => $customField['alias'],
                        'type'  => $customField['type'],
                    ],
                ],
            ]);
            $leadModel = self::getContainer()->get('mautic.lead.model.lead');
            \assert($leadModel instanceof LeadModel);
            $leadModel->setFieldValues($lead, [$customField['alias'] => $customField['value']]);
        }
        $contactRepo->saveEntity($lead);

        return $lead;
    }

    /**
     * @param array<mixed> $customField
     */
    public function createCompany(string $name, array $customField = []): Company
    {
        $companyRepo = $this->em->getRepository(Company::class);
        \assert($companyRepo instanceof CompanyRepository);
        $company = new Company();
        $company->setName($name);
        if (!empty($customField)) {
            $company->setFields([
                $customField['group'] => [
                    $customField['alias'] => [
                        'value' => '',
                        'type'  => $customField['type'],
                    ],
                ],
            ]);
            $companyModel = self::getContainer()->get('mautic.lead.model.company');
            \assert($companyModel instanceof CompanyModel);
            $companyModel->setFieldValues($company, [$customField['alias'] => $customField['value']]);
        }
        $companyRepo->saveEntity($company);

        return $company;
    }

    private function createCompanyLeadRelation(Company $company, Lead $lead): void
    {
        $companyLead = new CompanyLead();
        $companyLead->setCompany($company);
        $companyLead->setLead($lead);
        $companyLead->setDateAdded(new \DateTime());

        $this->em->persist($companyLead);
    }

    /**
     * @throws ORMException
     */
    private function createSegmentMember(LeadList $segment, Lead $lead): void
    {
        $segmentMember = new ListLead();
        $segmentMember->setLead($lead);
        $segmentMember->setList($segment);
        $segmentMember->setDateAdded(new \DateTime());
        $this->em->persist($segmentMember);
    }

    /**
     * @throws ORMException
     */
    private function createCampaign(string $campaignName, LeadList $segment): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName($campaignName);
        $campaign->setIsPublished(true);
        $campaign->addList($segment);
        $this->em->persist($campaign);

        return $campaign;
    }

    /**
     * @param array<mixed> $property
     *
     * @throws ORMException
     */
    protected function createEvent(
        string $name,
        Campaign $campaign,
        string $type,
        string $eventType,
        array $property = null,
        string $decisionPath = '',
        Event $parentEvent = null,
    ): Event {
        $event = new Event();
        $event->setName($name);
        $event->setCampaign($campaign);
        $event->setType($type);
        $event->setEventType($eventType);
        $event->setTriggerInterval(1);
        $event->setProperties($property);
        $event->setTriggerMode('immediate');
        $event->setDecisionPath($decisionPath);
        $event->setParent($parentEvent);
        $this->em->persist($event);

        return $event;
    }
}
