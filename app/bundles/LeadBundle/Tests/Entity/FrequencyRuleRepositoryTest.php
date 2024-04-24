<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\EmailBundle\Entity\Stat;
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

    protected function setUp(): void
    {
        parent::setUp();
        $this->frequencyRuleRepository = static::getContainer()->get('mautic.lead.repository.frequency_rule');
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function testCustomFrequencyRuleViolationsMethodReturnsCorrectData(): void
    {
        $lead = new Lead();
        $lead->setFirstname('Firstname');
        $lead->setLastname('Lastname');
        $lead->setEmail('test@test.com');
        $lead->setPhone('555-666-777');

        $this->em->persist($lead);

        $frequencyRule = new FrequencyRule();
        $frequencyRule->setFrequencyNumber(1);
        $frequencyRule->setFrequencyTime('DAY');
        $frequencyRule->setChannel('email');
        $frequencyRule->setDateAdded(new \DateTime());
        $frequencyRule->setLead($lead);

        $this->em->persist($frequencyRule);

        $emailStats1 = new Stat();
        $emailStats1->setLead($lead);
        $emailStats1->setEmailAddress('testemail@test.test');
        $emailStats1->setDateSent(new \DateTime());
        $emailStats1->setIsRead(true);
        $emailStats1->setIsFailed(false);
        $emailStats1->setViewedInBrowser(false);

        $emailStats2 = new Stat();
        $emailStats2->setLead($lead);
        $emailStats2->setEmailAddress('testemail@test.test');
        $emailStats2->setDateSent(new \DateTime());
        $emailStats2->setIsRead(true);
        $emailStats2->setIsFailed(false);
        $emailStats2->setViewedInBrowser(false);

        $this->em->persist($emailStats1);
        $this->em->persist($emailStats2);
        $this->em->flush();

        $violations         = $this->frequencyRuleRepository->getAppliedFrequencyRules('email', [$lead->getId()], 1, 'DAY');
        $expectedViolations = [
            [
                'lead_id'          => (string) $lead->getId(),
                'frequency_number' => '1',
                'frequency_time'   => 'DAY',
            ],
        ];
        Assert::assertSame($expectedViolations, $violations);
    }

    public function testValidateDefaultParameters(): void
    {
        $method = new \ReflectionMethod(FrequencyRuleRepository::class, 'validateDefaultParameters');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($this->frequencyRuleRepository, false, false));
        $this->assertFalse($method->invoke($this->frequencyRuleRepository, false, true));
        $this->assertFalse($method->invoke($this->frequencyRuleRepository, true, false));
        $this->assertTrue($method->invoke($this->frequencyRuleRepository, true, true));
    }
}
