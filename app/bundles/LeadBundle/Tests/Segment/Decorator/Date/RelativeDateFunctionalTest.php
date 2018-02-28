<?php

namespace Mautic\LeadBundle\Tests\Segment\Decorator\Date;

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Test\MauticWebTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Segment\ContactSegmentService;

/**
 * Class RelativeDateFunctionalTest.
 */
class RelativeDateFunctionalTest extends MauticWebTestCase
{
    public function testSegmentCountIsCorrectForToday()
    {
        $name = 'Today';
        $lead = $this->createLead($name, 'midnight today', '+10 seconds');

        $this->checkSegmentResult($name, $lead);
    }

    public function testSegmentCountIsCorrectForTomorrow()
    {
        $name = 'Tomorrow';
        $lead = $this->createLead($name, 'midnight tomorrow', '+10 seconds');

        $this->checkSegmentResult($name, $lead);
    }

    public function testSegmentCountIsCorrectForYesterday()
    {
        $name = 'Yesterday';
        $lead = $this->createLead($name, 'midnight today', '-10 seconds');

        $this->checkSegmentResult($name, $lead);
    }

    public function testSegmentCountIsCorrectForWeekLast()
    {
        $name = 'Last week';
        $lead = $this->createLead($name, 'midnight monday last week', '+2 days');

        $this->checkSegmentResult($name, $lead);
    }

    public function testSegmentCountIsCorrectForWeekNext()
    {
        $name = 'Next week';
        $lead = $this->createLead($name, 'midnight monday next week', '+2 days');

        $this->checkSegmentResult($name, $lead);
    }

    public function testSegmentCountIsCorrectForWeekThis()
    {
        $name = 'This week';
        $lead = $this->createLead($name, 'midnight monday this week', '+2 days');

        $this->checkSegmentResult($name, $lead);
    }

    public function testSegmentCountIsCorrectForMonthLast()
    {
        $name = 'Last month';
        $lead = $this->createLead($name, 'midnight first day of last month', '+2 days');

        $this->checkSegmentResult($name, $lead);
    }

    public function testSegmentCountIsCorrectForMonthNext()
    {
        $name = 'Next month';
        $lead = $this->createLead($name, 'midnight first day of next month', '+2 days');

        $this->checkSegmentResult($name, $lead);
    }

    public function testSegmentCountIsCorrectForMonthThis()
    {
        $name = 'This month';
        $lead = $this->createLead($name, 'midnight first day of this month', '+2 days');

        $this->checkSegmentResult($name, $lead);
    }

    public function testSegmentCountIsCorrectForYearLast()
    {
        $name = 'Last year';
        $lead = $this->createLead($name, 'midnight first day of last year', '+2 days');

        $this->checkSegmentResult($name, $lead);
    }

    public function testSegmentCountIsCorrectForYearNext()
    {
        $name = 'Next year';
        $lead = $this->createLead($name, 'midnight first day of next year', '+2 days');

        $this->checkSegmentResult($name, $lead);
    }

    /**
     * @param string $name
     * @param Lead   $lead
     */
    private function checkSegmentResult($name, Lead $lead)
    {
        /** @var ContactSegmentService $contactSegmentService */
        $contactSegmentService = $this->container->get('mautic.lead.model.lead_segment_service');

        $alias = strtolower(InputHelper::alphanum($name, false, '-'));

        $segmentName     = 'segment-with-relative-date-'.$alias;
        $segmentRef      = $this->fixtures->getReference($segmentName);
        $segmentContacts = $contactSegmentService->getTotalLeadListLeadsCount($segmentRef);

        $this->removeAllDateRelatedLeads(); //call before assert to be sure cleaning will process

        $this->assertEquals(
            1,
            $segmentContacts[$segmentRef->getId()]['count'],
            'There should be 1 contacts in the '.$segmentName.' segment.'
        );
        $this->assertEquals(
            $lead->getId(),
            $segmentContacts[$segmentRef->getId()]['maxId'],
            'MaxId in the '.$segmentName.' segment should be ID of Lead.'
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
        $this->removeAllDateRelatedLeads();

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

    private function removeAllDateRelatedLeads()
    {
        // Remove all date related leads to not affect other test
        $this->em->getConnection()->query(sprintf("DELETE FROM %sleads WHERE lastname = 'Date';", MAUTIC_TABLE_PREFIX));
    }
}
