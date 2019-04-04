<?php

namespace Mautic\LeadBundle\Tests\Segment\Decorator\Date;

use Doctrine\ORM\EntityManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\ContactSegmentFilterFactory;
use Mautic\LeadBundle\Segment\ContactSegmentService;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Tests\DataFixtures\Traits\FixtureObjectsTrait;

/**
 * Class RelativeDateFunctionalTest.
 */
class RelativeDateFunctionalTest extends WebTestCase
{
    use FixtureObjectsTrait;

    /** @var EntityManager */
    private $entityManager;

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

    public function testSegmentCountIsCorrectForRelativePlus()
    {
        $name = 'Relative plus';
        $lead = $this->createLead($name, 'now', '+5 days');

        $this->checkSegmentResult($name, $lead);
    }

    public function testSegmentCountIsCorrectForRelativeMinus()
    {
        $name = 'Relative minus';
        $lead = $this->createLead($name, 'now', '-4 days');

        $this->checkSegmentResult($name, $lead);
    }

    protected function setUp(): void
    {
        parent::setUp();

        /** @var EntityManager $entityManager */
        $entityManager       = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->entityManager = $entityManager;
    }

        public function testRelativeOperators()
    {
        $fixturesDirectory = $this->getFixturesDirectory();
        $objects           = $this->loadFixtureFiles([
            $fixturesDirectory.'/roles.yml',
            $fixturesDirectory.'/users.yml',
            $fixturesDirectory.'/leads.yml',
        ], false, null, 'doctrine'); //,ORMPurger::PURGE_MODE_DELETE);

        $this->setFixtureObjects($objects);

        /** @var Registr $connection */
        $connection            = $this->entityManager->getConnection();

        /** @var ContactSegmentFilterFactory $filterFactory */
        $filterFactory = $this->getContainer()->get('mautic.lead.model.lead_segment_filter_factory');

        $crateArguments = [
            'glue'     => 'and',
            'field'    => 'date_added',
            'object'   => ContactSegmentFilterCrate::CONTACT_OBJECT,
            'type'     => 'date',
            'filter'   => null,
            'operator' => 'gte',
        ];

        $filterValues = [
            [3, 'gte', '-3 day'],
            [0, 'gt', '-3 day ago'],
            [3, 'gte', '3 day ago'],
            [2, 'gt', '3 day ago'],
            [4, 'gt', '5 day ago'],
            [5, 'gte', '5 day ago'],
            [5, 'gt', '6 day ago'],
            [6, 'gte', '6 day ago'],
        ];

        foreach ($filterValues as $filterValue) {
            $queryBuilder = new QueryBuilder($connection);
            $queryBuilder->select('l.id, l.date_added')->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

            $crateArguments['operator'] = $filterValue[1];
            $crateArguments['filter']   = $filterValue[2];
            $filter                     = $filterFactory->factorSegmentFilter($crateArguments);

            $filter->applyQuery($queryBuilder);
            $result = $queryBuilder->execute();

            $this->assertEquals($filterValue[0], $result->rowCount());
        }
        $this->unloadFixtures();
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

        $date = new \DateTime($initialTime, new \DateTimeZone('UTC'));
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

    protected function unloadFixtures(): void
    {
        foreach ($this->getFixturesInUnloadableOrder() as $entity) {
            $this->entityManager->remove($entity);
        }

        $this->entityManager->flush();

        parent::tearDown();
    }
}
