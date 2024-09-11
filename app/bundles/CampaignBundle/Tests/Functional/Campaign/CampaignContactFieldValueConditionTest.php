<?php

namespace Mautic\CampaignBundle\Tests\Functional\Campaign;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Tests\Command\AbstractCampaignCommand;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

class CampaignContactFieldValueConditionTest extends AbstractCampaignCommand
{
    /**
     * @dataProvider dataProperties
     *
     * @param array<mixed> $properties
     */
    public function testContactFieldValueConditionWithBetweenOperator(\DateTime $lead1Date, \DateTime $lead2Date, array $properties): void
    {
        $lead1 = $this->createLead('Lead1');
        $lead1->setLastActive($lead1Date);

        $lead2 = $this->createLead('Lead2');
        $lead2->setLastActive($lead2Date);

        $campaign = $this->createCampaign('Test Campaign');

        $this->createCampaignLead($campaign, $lead1);
        $this->createCampaignLead($campaign, $lead2);

        $campaignConditionEvent = $this->setupContactFieldValueCondition($campaign, $properties);

        $campaignAction = new Event();
        $campaignAction->setCampaign($campaign);
        $campaignAction->setParent($campaignConditionEvent);
        $campaignAction->setName('Update Contact Points');
        $campaignAction->setType('lead.changepoints');
        $campaignAction->setEventType('action');
        $campaignAction->setProperties(['points' => 5]);
        $campaignAction->setDecisionPath('yes');
        $this->em->persist($campaignAction);

        $this->em->flush();
        $this->em->clear();

        $result = $this->testSymfonyCommand('mautic:campaigns:trigger', ['--campaign-id' => $campaign->getId()]);
        Assert::assertStringContainsString('3 total events were executed', $result->getDisplay());

        $leadRepository = $this->em->getRepository(Lead::class);
        $lead1          = $leadRepository->find($lead1->getId());
        Assert::assertEquals(5, $lead1->getPoints());

        $lead2 = $leadRepository->find($lead2->getId());
        Assert::assertEquals(0, $lead2->getPoints());
    }

    /**
     * @param array<mixed> $properties
     */
    private function setupContactFieldValueCondition(Campaign $campaign, array $properties = []): Event
    {
        $campaignCondition = new Event();
        $campaignCondition->setCampaign($campaign);
        $campaignCondition->setName('Contact Field Value Condition');
        $campaignCondition->setType('lead.field_value');
        $campaignCondition->setEventType('condition');
        $campaignCondition->setProperties($properties);
        $this->em->persist($campaignCondition);

        return $campaignCondition;
    }

    /**
     * @return array<mixed> iterable
     */
    public function dataProperties(): iterable
    {
        yield 'Between' => [
            new \DateTime('+1 day'),
            new \DateTime('-1 day'),
            [
                'field'    => 'last_active',
                'operator' => 'between',
                'value'    => [
                    'date_from' => (new \DateTime())->format('M j, Y'),
                    'date_to'   => (new \DateTime('+1 day'))->format('M j, Y'),
                ],
            ],
        ];
        yield 'Greater than absolute date' => [
            new \DateTime('+1 day'),
            new \DateTime('-1 day'),
            [
                'field'    => 'last_active',
                'operator' => 'gt',
                'value'    => [
                    'absoluteDate'             => (new \DateTime())->format('Y-m-d H:i'),
                    'dateTypeMode'             => 'absolute',
                    'relativeDateInterval'     => '1',
                    'relativeDateIntervalUnit' => 'd',
                ],
            ],
        ];
        yield 'Greater than equal to absolute date' => [
            new \DateTime('+1 day'),
            new \DateTime('-1 day'),
            [
                'field'    => 'last_active',
                'operator' => 'gte',
                'value'    => [
                    'absoluteDate'             => (new \DateTime())->format('Y-m-d H:i'),
                    'dateTypeMode'             => 'absolute',
                    'relativeDateInterval'     => '1',
                    'relativeDateIntervalUnit' => 'd',
                ],
            ],
        ];
        yield 'Greater than equal to absolute date with date modifiers' => [
            new \DateTime('+1 day'),
            new \DateTime('-1 day'),
            [
                'field'    => 'last_active',
                'operator' => 'gte',
                'value'    => [
                    'absoluteDate'             => 'today',
                    'dateTypeMode'             => 'absolute',
                    'relativeDateInterval'     => '1',
                    'relativeDateIntervalUnit' => 'd',
                ],
            ],
        ];
        yield 'Less than absolute date' => [
            new \DateTime('-1 day'),
            new \DateTime('+1 day'),
            [
                'field'    => 'last_active',
                'operator' => 'lt',
                'value'    => [
                    'absoluteDate'             => (new \DateTime())->format('Y-m-d H:i'),
                    'dateTypeMode'             => 'absolute',
                    'relativeDateInterval'     => '1',
                    'relativeDateIntervalUnit' => 'd',
                ],
            ],
        ];
        yield 'Less than equal to absolute date' => [
            new \DateTime('-1 day'),
            new \DateTime('+1 day'),
            [
                'field'    => 'last_active',
                'operator' => 'lte',
                'value'    => [
                    'absoluteDate'             => (new \DateTime())->format('Y-m-d H:i'),
                    'dateTypeMode'             => 'absolute',
                    'relativeDateInterval'     => '1',
                    'relativeDateIntervalUnit' => 'd',
                ],
            ],
        ];
        yield 'Greater than absolute date for Backward compatibility' => [
            new \DateTime('+1 day'),
            new \DateTime('-1 day'),
            [
                'field'    => 'last_active',
                'operator' => 'gt',
                'value'    => (new \DateTime())->format('Y-m-d H:i'),
            ],
        ];
        yield 'Greater than relative date' => [
            new \DateTime('+2 day'),
            new \DateTime('-1 day'),
            [
                'field'    => 'last_active',
                'operator' => 'gt',
                'value'    => [
                    'absoluteDate'             => '',
                    'dateTypeMode'             => 'relative',
                    'relativeDateInterval'     => '1',
                    'relativeDateIntervalUnit' => 'd',
                ],
            ],
        ];
        yield 'Greater than equal to relative date' => [
            new \DateTime('+1 day'),
            new \DateTime('-1 day'),
            [
                'field'    => 'last_active',
                'operator' => 'gte',
                'value'    => [
                    'absoluteDate'             => '',
                    'dateTypeMode'             => 'relative',
                    'relativeDateInterval'     => '1',
                    'relativeDateIntervalUnit' => 'd',
                ],
            ],
        ];
        yield 'Less than relative date' => [
            new \DateTime('-1 day'),
            new \DateTime('+1 day'),
            [
                'field'    => 'last_active',
                'operator' => 'lt',
                'value'    => [
                    'absoluteDate'             => '',
                    'dateTypeMode'             => 'relative',
                    'relativeDateInterval'     => '1',
                    'relativeDateIntervalUnit' => 'd',
                ],
            ],
        ];
        yield 'Less than equal to relative date' => [
            new \DateTime('-1 day'),
            new \DateTime('+1 day'),
            [
                'field'    => 'last_active',
                'operator' => 'lte',
                'value'    => [
                    'absoluteDate'             => '',
                    'dateTypeMode'             => 'relative',
                    'relativeDateInterval'     => '1',
                    'relativeDateIntervalUnit' => 'd',
                ],
            ],
        ];
    }
}
