<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Entity\ListLead;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Functional tests for ContactSegmentFilterFactory.
 *
 * @see \Mautic\LeadBundle\Segment\ContactSegmentFilterFactory
 */
final class ContactSegmentFilterFactoryFunctionalTest extends MauticMysqlTestCase
{
    /**
     * Test that segments with 3+ date filters using OR logic don't throw TypeError.
     *
     * When multiple OR'd date filters with '=' operator exist, the mergeFilters() method
     * was grouping them into a single IN filter with an array value. This caused
     * DateOptionFactory::getDateOption() to fail because str_contains() expects a string.
     *
     * This test creates leads with different date_identified values and verifies that:
     * 1. The segment update command completes without TypeError
     * 2. The correct leads are included/excluded from the segment
     *
     * @see https://github.com/mautic/mautic/issues/15701
     */
    public function testSegmentWithMultipleDateFiltersAndOrLogicDoesNotThrowTypeError(): void
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);
        $applicationTester = new ApplicationTester($application);

        /** @var LeadRepository $leadRepository */
        $leadRepository = $this->em->getRepository(Lead::class);

        // Create leads with different date_identified values
        $leadToday = new Lead();
        $leadToday->setFirstname('Today');
        $leadToday->setLastname('Lead');
        $leadToday->setDateIdentified(new \DateTime('midnight today +10 seconds'));
        $leadRepository->saveEntity($leadToday);

        $leadYesterday = new Lead();
        $leadYesterday->setFirstname('Yesterday');
        $leadYesterday->setLastname('Lead');
        $leadYesterday->setDateIdentified(new \DateTime('midnight today -10 seconds'));
        $leadRepository->saveEntity($leadYesterday);

        $leadLastWeek = new Lead();
        $leadLastWeek->setFirstname('LastWeek');
        $leadLastWeek->setLastname('Lead');
        // Use a date that's definitely within "last week" range
        $leadLastWeek->setDateIdentified(new \DateTime('midnight monday last week +2 days'));
        $leadRepository->saveEntity($leadLastWeek);

        // This lead should NOT match any filter (2 months ago)
        $leadOld = new Lead();
        $leadOld->setFirstname('Old');
        $leadOld->setLastname('Lead');
        $leadOld->setDateIdentified(new \DateTime('-2 months'));
        $leadRepository->saveEntity($leadOld);

        // Create segment with 3+ OR'd date filters (this triggers the bug)
        $segment = new LeadList();
        $segment->setName('Test Multiple Date Filters');
        $segment->setAlias('test_multiple_date_filters_'.uniqid());
        $segment->setPublicName('Test Multiple Date Filters');
        $segment->setIsPublished(true);
        $segment->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'date_identified',
                'object'   => 'lead',
                'type'     => 'datetime',
                'operator' => '=',
                'filter'   => 'today',
                'display'  => null,
            ],
            [
                'glue'     => 'or',
                'field'    => 'date_identified',
                'object'   => 'lead',
                'type'     => 'datetime',
                'operator' => '=',
                'filter'   => 'yesterday',
                'display'  => null,
            ],
            [
                'glue'     => 'or',
                'field'    => 'date_identified',
                'object'   => 'lead',
                'type'     => 'datetime',
                'operator' => '=',
                'filter'   => 'last week',
                'display'  => null,
            ],
        ]);

        $this->em->persist($segment);
        $this->em->flush();

        // Run the segment update command - this throws TypeError without the fix
        $exitCode = $applicationTester->run([
            'command' => 'mautic:segments:update',
            '-i'      => $segment->getId(),
        ]);

        $this->assertSame(0, $exitCode, 'Segment update command should complete successfully without TypeError: '.$applicationTester->getDisplay());

        // Verify segment membership - get all leads in the segment
        $segmentMembers = $this->em->getRepository(ListLead::class)->findBy(['list' => $segment->getId()]);
        $memberLeadIds  = array_map(fn (ListLead $member) => $member->getLead()->getId(), $segmentMembers);

        // Leads matching the date filters should be in the segment
        $this->assertContains($leadToday->getId(), $memberLeadIds, 'Lead identified today should be in segment');
        $this->assertContains($leadYesterday->getId(), $memberLeadIds, 'Lead identified yesterday should be in segment');
        $this->assertContains($leadLastWeek->getId(), $memberLeadIds, 'Lead identified last week should be in segment');

        // Lead that doesn't match any filter should NOT be in the segment
        $this->assertNotContains($leadOld->getId(), $memberLeadIds, 'Lead identified 2 months ago should NOT be in segment');
    }
}
