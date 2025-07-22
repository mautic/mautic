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
use Mautic\LeadBundle\Tests\Traits\LeadFieldTestTrait;
use PHPUnit\Framework\Assert;

class CampaignDecisionTest extends MauticMysqlTestCase
{
    use CampaignEntitiesTrait;
    use LeadFieldTestTrait;
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
        array $additionalValue = [],
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
        $this->createField($fieldDetails);

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
        array $noEventLeads,
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
     * @return iterable<string, mixed>
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
