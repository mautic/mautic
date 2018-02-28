<?php

namespace Mautic\LeadBundle\Tests\Segment\Decorator\Date;

use Mautic\CoreBundle\Test\MauticWebTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Segment\ContactSegmentService;

/**
 * Class RelativeDateFunctionalTest.
 */
class RelativeDateFunctionalTest extends MauticWebTestCase
{
    public function testSegmentCountIsCorrectForTomorrow()
    {
        $lead = $this->createLead('Tomorrow', 'midnight tomorrow', '+10 seconds');

        /** @var ContactSegmentService $contactSegmentService */
        $contactSegmentService = $this->container->get('mautic.lead.model.lead_segment_service');

        $segmentTomorrowRef = $this->fixtures->getReference('segment-with-relative-date-tomorrow');
        $segmentContacts    = $contactSegmentService->getTotalLeadListLeadsCount($segmentTomorrowRef);

        $this->assertEquals(
            1,
            $segmentContacts[$segmentTomorrowRef->getId()]['count'],
            'There should be 1 contacts in the segment-with-relative-date-tomorrow segment.'
        );
        $this->assertEquals(
            $lead->getId(),
            $segmentContacts[$segmentTomorrowRef->getId()]['maxId'],
            'MaxId in the segment-with-relative-date-tomorrow segment should be ID of Lead.'
        );
    }

    public function testSegmentCountIsCorrectForLastWeek()
    {
        $lead = $this->createLead('Last week', 'midnight monday last week', '+2 days');

        /** @var ContactSegmentService $contactSegmentService */
        $contactSegmentService = $this->container->get('mautic.lead.model.lead_segment_service');

        $segmentLastWeekRef = $this->fixtures->getReference('segment-with-relative-date-last-week');
        $segmentContacts    = $contactSegmentService->getTotalLeadListLeadsCount($segmentLastWeekRef);

        $this->assertEquals(
            1,
            $segmentContacts[$segmentLastWeekRef->getId()]['count'],
            'There should be 1 contacts in the segment-with-relative-date-last-week segment.'
        );
        $this->assertEquals(
            $lead->getId(),
            $segmentContacts[$segmentLastWeekRef->getId()]['maxId'],
            'MaxId in the segment-with-relative-date-last-week segment should be ID of Lead.'
        );
    }

    /**
     * @param string $name
     * @param string $initialTime
     * @param string $dateModifier
     *
     * @return Lead
     */
    private function createLead($name, $initialTime, $dateModifier)
    {
        // Remove the title from all contacts, rebuild the list, and check that list is updated
        $this->em->getConnection()->query(sprintf("DELETE FROM %sleads WHERE lastname = 'Date';", MAUTIC_TABLE_PREFIX));

        /** @var LeadRepository $leadRepository */
        $leadRepository = $this->container->get('doctrine.orm.default_entity_manager')->getRepository(Lead::class);

        $date = new \DateTime($initialTime);
        $date->modify($dateModifier);

        $lead = new Lead();
        $lead->setLastname('Date');
        $lead->setFirstname($name);
        $lead->setDateIdentified($date);

        $leadRepository->saveEntity($lead);

        return $lead;
    }
}
