<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\FrequencyRule;
use Mautic\LeadBundle\Entity\FrequencyRuleRepository;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

class FrequencyRuleRepositoryTest extends MauticMysqlTestCase
{
    /**
     * @var FrequencyRuleRepository
     */
    private $frequencyRuleRepository;

    /**
     * @var string
     */
    private $prefix;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prefix                  = self::$container->getParameter('mautic.db_table_prefix');
        $this->frequencyRuleRepository = self::$container->get('mautic.lead.repository.frequency_rule');
    }

    public function testCustomFrequencyRuleViolationsMethodReturnsCorrectData()
    {
        $lead = new Lead();
        $lead->setFirstname('Firstname');
        $lead->setLastname('Lastname');
        $lead->setEmail('test@test.com');
        $lead->setPhone('555-666-777');

        $this->em->persist($lead);
        $this->em->flush();

        /** @var FrequencyRule $frequencyRule */
        $frequencyRule = new FrequencyRule();
        $frequencyRule->setFrequencyNumber(1);
        $frequencyRule->setFrequencyTime('DAY');
        $frequencyRule->setChannel('email');
        $frequencyRule->setDateAdded(new \DateTime());
        $frequencyRule->setLead($lead);

        $this->em->persist($frequencyRule);
        $this->em->flush();

        $this->connection->insert($this->prefix.'email_stats', [
            'lead_id'           => $lead->getId(),
            'email_address'     => 'testemail@test.test',
            'date_sent'         => (new \DateTime())->format('Y-m-d H:i:s'),
            'is_read'           => 1,
            'is_failed'         => 0,
            'viewed_in_browser' => 0,
        ]);

        $this->connection->insert($this->prefix.'email_stats', [
            'lead_id'           => $lead->getId(),
            'email_address'     => 'testemail2@test.test',
            'date_sent'         => (new \DateTime())->format('Y-m-d H:i:s'),
            'is_read'           => 1,
            'is_failed'         => 0,
            'viewed_in_browser' => 0,
        ]);

        $violations         = $this->frequencyRuleRepository->getAppliedFrequencyRules('email', [1], 1, 1, 'email_stats', 'lead_id', 'date_sent');
        $expectedViolations = [
            0 => [
                    'lead_id'          => '1',
                    'frequency_number' => '1',
                    'frequency_time'   => 'DAY',
                ],
        ];
        Assert::assertSame($expectedViolations, $violations);
    }
}
