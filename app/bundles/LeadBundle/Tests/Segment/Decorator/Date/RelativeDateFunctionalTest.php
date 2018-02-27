<?php

namespace Mautic\LeadBundle\Tests\Segment\Decorator\Date;

use Mautic\CoreBundle\Test\MauticWebTestCase;
use Mautic\LeadBundle\Segment\ContactSegmentService;

/**
 * Class RelativeDateFunctionalTest.
 */
class RelativeDateFunctionalTest extends MauticWebTestCase
{
    public function testSegmentCountIsCorrectForLastWeek()
    {
        /** @var ContactSegmentService $contactSegmentService */
        $contactSegmentService = $this->container->get('mautic.lead.model.lead_segment_service');

        $segmentLastWeekRef = $this->fixtures->getReference('segment-with-relative-date-last-week');
        $segmentContacts    = $contactSegmentService->getTotalLeadListLeadsCount($segmentLastWeekRef);

        $leadLastWeekRef = $this->fixtures->getReference('lead-date-last-week');

        $this->assertEquals(
            1,
            $segmentContacts[$segmentLastWeekRef->getId()]['count'],
            'There should be 1 contacts in the segment-with-relative-date-last-week segment.'
        );
        $this->assertEquals(
            $leadLastWeekRef->getId(),
            $segmentContacts[$segmentLastWeekRef->getId()]['maxId'],
            'MaxId in the segment-with-relative-date-last-week segment should be ID of Lead.'
        );
    }

    public function testSegmentCountIsCorrectForTomorrow()
    {
        /** @var ContactSegmentService $contactSegmentService */
        $contactSegmentService = $this->container->get('mautic.lead.model.lead_segment_service');

        $segmentTomorrowRef = $this->fixtures->getReference('segment-with-relative-date-tomorrow');
        $segmentContacts    = $contactSegmentService->getTotalLeadListLeadsCount($segmentTomorrowRef);

        $leadTomorrowRef = $this->fixtures->getReference('lead-date-tomorrow');

        $this->assertEquals(
            1,
            $segmentContacts[$segmentTomorrowRef->getId()]['count'],
            'There should be 1 contacts in the segment-with-relative-date-tomorrow segment.'
        );
        $this->assertEquals(
            $leadTomorrowRef->getId(),
            $segmentContacts[$segmentTomorrowRef->getId()]['maxId'],
            'MaxId in the segment-with-relative-date-tomorrow segment should be ID of Lead.'
        );
    }
}
