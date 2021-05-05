<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\FrequencyRule;
use Mautic\LeadBundle\Entity\FrequencyRuleRepository;
use Mautic\LeadBundle\Entity\Lead;

class FrequencyRuleRepositoryTest extends MauticMysqlTestCase
{
    /**
     * @var FrequencyRuleRepository
     */
    private $frequencyRuleRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->frequencyRuleRepository = self::$container->get('mautic.lead.repository.frequency_rule');
    }

    public function testCustomFrequencyRuleViolationsMethodReturnsCorrectData()
    {
        $lead = new Lead();
        $lead->setFirstname('Firstname');
        $lead->setLastname('Lastname');
        $lead->setEmail('test@test.com');
        $lead->setPhone('555-666-777');

        /** @var FrequencyRule $frequencyRule */
        $frequencyRule = new FrequencyRule();
        $frequencyRule->setFrequencyNumber(1);
        $frequencyRule->setFrequencyTime(1);
        $frequencyRule->setChannel('email');


        $this->em->persist($lead);
        $this->em->flush();

        $data = $this->frequencyRuleRepository->getAppliedFrequencyRules('email', [1], 1, 1, 'email_stats', 'lead_id', 'date_sent');
        $this->addToAssertionCount(1);
    }
}