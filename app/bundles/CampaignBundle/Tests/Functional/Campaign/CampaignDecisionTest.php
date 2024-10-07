<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Campaign;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\Mapping\MappingException;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
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
use PHPUnit\Framework\Assert;

class CampaignDecisionTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * @dataProvider dataProviderLeadSelect
     *
     * @param array<mixed> $additionalValue
     *
     * @throws OptimisticLockException
     * @throws ORMException
     * @throws MappingException
     */
    public function testCampaignContactFieldValueDecision(
        string $object,
        string $type,
        string $operator,
        array $additionalValue = []
    ): void {
        $fieldDetails = [
            'alias'               => 'select_field',
            'type'                => $type,
            'group'               => 'core',
            'object'              => $object,
            'properties'          => [
                'list' => [
                    ['label' => 'l1', 'value' => 'v1'],
                    ['label' => 'l2', 'value' => 'v2'],
                    ['label' => 'l3', 'value' => 'v3'],
                    ['label' => 'l4', 'value' => 'v4'],
                    ['label' => 'l5', 'value' => 'v5'],
                ],
            ],
        ];
        $this->makeField($fieldDetails);

        $segment  = $this->createSegment('seg1', []);
        $lead1    = $this->createLeadData($segment, $object, $fieldDetails, $additionalValue, 1);
        $lead2    = $this->createLeadData($segment, $object, $fieldDetails, $additionalValue, 2);
        $lead3    = $this->createLeadData($segment, $object, $fieldDetails, $additionalValue, 3);
        $lead4    = $this->createLeadData($segment, $object, $fieldDetails, $additionalValue, 4);
        $lead5    = $this->createLeadData($segment, $object, [], [], 5);
        $campaign = $this->createCampaign('c1', $segment);

        $parentEvent = $this->createEvent('Field Value Condition', $campaign,
            'lead.field_value',
            'condition',
            [
                'field'    => $fieldDetails['alias'],
                'operator' => $operator,
                'value'    => [
                    'v1', 'v3',
                ],
            ]
        );

        $yesEvent = $this->createEvent('Add 10 points', $campaign,
            'lead.changepoints',
            'action',
            ['points' => 10],
            'yes',
            $parentEvent
        );

        $noEvent = $this->createEvent('Add 5 points', $campaign,
            'lead.changepoints',
            'action',
            ['points' => 5],
            'no',
            $parentEvent
        );

        $this->em->flush();
        $this->em->clear();

        $this->testSymfonyCommand('mautic:campaigns:update', ['--campaign-id' => $campaign->getId()]);
        $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()]);
        if ('in' === $operator) {
            $this->assertCampaignLeadEventLog(
                $campaign,
                $yesEvent,
                $noEvent,
                [$lead1->getId(), $lead3->getId()],
                [$lead2->getId(), $lead4->getId(), $lead5->getId()]
            );
        } else {
            $this->assertCampaignLeadEventLog(
                $campaign,
                $noEvent,
                $yesEvent,
                [$lead1->getId(), $lead3->getId()],
                [$lead2->getId(), $lead4->getId(), $lead5->getId()]
            );
        }
    }

    /**
     * @param array<int> $yesEventLeads
     * @param array<int> $noEventLeads
     */
    private function assertCampaignLeadEventLog(
        Campaign $campaign,
        Event $yesEvent,
        Event $noEvent,
        array $yesEventLeads,
        array $noEventLeads
    ): void {
        $campaignEventLogs = $this->em->getRepository(LeadEventLog::class)
            ->findBy(['campaign' => $campaign, 'event' => $yesEvent], ['event' => 'ASC']);
        Assert::assertCount(count($yesEventLeads), $campaignEventLogs);
        Assert::assertSame(
            $yesEventLeads,
            $this->getLeadIds($campaignEventLogs)
        );

        $campaignEventLogs = $this->em->getRepository(LeadEventLog::class)
            ->findBy(['campaign' => $campaign, 'event' => $noEvent], ['event' => 'ASC']);
        Assert::assertCount(count($noEventLeads), $campaignEventLogs);
        Assert::assertSame(
            $noEventLeads,
            $this->getLeadIds($campaignEventLogs)
        );
    }

    /**
     * @param array<mixed> $campaignEventLogs
     *
     * @return array<int>
     */
    private function getLeadIds(array $campaignEventLogs): array
    {
        $leadIds = [];
        foreach ($campaignEventLogs as $log) {
            \assert($log instanceof LeadEventLog);
            $leadIds[] = $log->getLead()->getId();
        }

        return $leadIds;
    }

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

        $fieldModel = self::$container->get('mautic.lead.model.field');
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
            $leadModel = self::$container->get('mautic.lead.model.lead');
            \assert($leadModel instanceof LeadModel);
            $leadModel->setFieldValues($lead, [$customField['alias'] => $customField['value']]);
        }
        $contactRepo->saveEntity($lead);

        return $lead;
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
        Event $parentEvent = null
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
            $companyModel = self::$container->get('mautic.lead.model.company');
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
     * @param array<mixed> $fieldDetails
     * @param array<mixed> $additionalValue
     */
    private function createLeadData(
        LeadList $segment,
        string $object,
        array $fieldDetails,
        array $additionalValue,
        int $index
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
     * @return iterable<string, mixed[]>
     */
    public function dataProviderLeadSelect(): iterable
    {
        yield 'With include filter for contact select field' => ['lead', 'select', 'in'];
        yield 'With exclude filter for contact select field' => ['lead', 'select', '!in'];
        yield 'With include filter for contact multiselect field' => ['lead', 'multiselect', 'in', ['v5']];
        yield 'With exclude filter for contact multiselect field' => ['lead', 'multiselect', '!in', ['v5']];
        yield 'With include filter for company select field' => ['company', 'select', 'in'];
        yield 'With exclude filter for company select field' => ['company', 'select', '!in'];
        yield 'With include filter for company multiselect field' => ['company', 'multiselect', 'in', ['v5']];
        yield 'With exclude filter for company multiselect field' => ['company', 'multiselect', '!in', ['v5']];
    }
}
