<?php

namespace Mautic\CampaignBundle\Tests\Model;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Tests\Mock\EventModelMock;
use Mautic\CampaignBundle\Tests\Mock\RepositoryMock;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class EventModelTest extends KernelTestCase
{
    private $model;
    private $container;
    private $totalProcess = 0;

    public function setUp()
    {
        self::bootKernel();

        $this->container = self::$kernel->getContainer();

        $this->model = $this->createMockClass(EventModelMock::class);
        $this->model->setEntityManager($emMock = $this->getMockEntityManager());
        $this->model->setLogger($this->container->get('monolog.logger.mautic'));

        $this->totalProcess = 0;
    }

    public function testTriggerQueuedEventsWithoutEventCount()
    {
        $campaign = $this->getCampaign();

        $result = $this->model->triggerQueuedEvents($campaign, $this->totalProcess, 10, 10, null, true);

        $this->assertEquals($this->getCountsArray(), $result);
    }

    public function testTriggerQueuedEventsWithNoLeads()
    {
        $iterations = 2;
        $campaign   = $this->getCampaign();
        $events     = [];

        for ($i = 0; $iterations > $i; ++$i) {
            $events[] = new Event();
        }

        $repository    = $this->getRepository($iterations, $events);
        $campaignModel = $this->getMockCampaignModel($events);
        $leadModel     = $this->getMockLeadModel();

        $this->model->setRepository($repository);
        $this->model->setCampaignModel($campaignModel);
        $this->model->setLeadModel($leadModel);

        $result = $this->model->triggerQueuedEvents($campaign, $this->totalProcess, 10, 10, null, true);

        $this->assertEquals($this->getCountsArray(), $result);
    }

    public function testTriggerQueuedEventsWithNoEvents()
    {
        $iterations    = 2;
        $campaign      = $this->getCampaign();
        $repository    = $this->getRepository($iterations, []);
        $campaignModel = $this->getMockCampaignModel();
        $leadModel     = $this->getMockLeadModel();

        $this->model->setRepository($repository);
        $this->model->setCampaignModel($campaignModel);
        $this->model->setLeadModel($leadModel);

        $result = $this->model->triggerQueuedEvents($campaign, $this->totalProcess, 10, 10, null, true);

        $this->assertEquals($this->getCountsArray($iterations), $result);
    }

    public function testTriggerQueuedEvents()
    {
        $iterations = 2;
        $campaign   = $this->getCampaign();
        $events     = [
            1 => [
                [
                    'id'       => 1,
                    'event_id' => 1,
                ],
                [
                    'id'       => 2,
                    'event_id' => 2,
                ],
            ],
        ];
        $campaignEvents = [
            1 => [],
            2 => [],
        ];
        $leads = [
            1 => [
                'id' => 1,
            ],
        ];
        $repository    = $this->getRepository($iterations, $events, $campaignEvents);
        $campaignModel = $this->getMockCampaignModel($events);
        $leadModel     = $this->getMockLeadModel($leads);

        $this->model->setRepository($repository);
        $this->model->setCampaignModel($campaignModel);
        $this->model->setLeadModel($leadModel);

        $result = $this->model->triggerQueuedEvents($campaign, $this->totalProcess, 10, 10, null, true);

        $this->assertEquals($this->getCountsArray($iterations, $iterations, $iterations, $iterations, $iterations), $result);
    }

    public function testExecuteEventQueued()
    {
        $leadEventLog = $this->executeEvent([
            'queued' => 1,
        ]);

        $this->assertEquals(true, $leadEventLog->getIsQueued());

        $leadEventLog = $this->executeEvent([
            'queued' => 0,
        ]);

        $this->assertEquals(false, $leadEventLog->getIsQueued());
    }

    /**
     * @param $eventResponse
     *
     * @return LeadEventLog
     */
    private function executeEvent($eventResponse)
    {
        $leadEventLog = new LeadEventLog();

        $emMock = $this->getMockEntityManager($leadEventLog);

        $campaignModel = $this->getMockCampaignModel();

        $this->model->setEntityManager($emMock);
        $this->model->setCampaignModel($campaignModel);
        $this->model->executeEvent                = true;
        $this->model->invokeEventCallbackResponse = $eventResponse;

        $event = [
            'id'          => 1,
            'eventType'   => 'eventType',
            'type'        => 'type',
            'properties'  => null,
            'triggerMode' => 'immediate',
        ];
        $campaign      = new Campaign();
        $lead          = new Lead(1);
        $eventSettings = [
            'eventType' => [
                'type' => 'type',
            ],
        ];
        $logExists           = 1;
        $evaluatedEventCount = 0;
        $executedEventCount  = 0;
        $totalEventCount     = 0;

        $this->assertEquals(false, $leadEventLog->getIsQueued());

        $this->model->executeEvent(
            $event,
            $campaign,
            $lead,
            $eventSettings,
            false,
            null,
            true,
            $logExists,
            $evaluatedEventCount,
            $executedEventCount,
            $totalEventCount
        );

        return $leadEventLog;
    }

    /**
     * @param $count
     * @param $events
     *
     * @return RepositoryMock
     */
    public function getRepository($count, array $events = [], array $campaignEvents = [])
    {
        $repository = new RepositoryMock();
        $repository->setQueuedEventsCount($count);
        $repository->setQueuedEvents($events);
        $repository->setCampaignEvents($campaignEvents);

        return $repository;
    }

    /**
     * @return Campaign
     */
    private function getCampaign()
    {
        $campaign = new Campaign();
        $campaign->setName('Test');

        return $campaign;
    }

    /**
     * @param int $events
     * @param int $evaluated
     * @param int $executed
     * @param int $totalEvaluated
     * @param int $totalExecuted
     *
     * @return array
     */
    private function getCountsArray($events = 0, $evaluated = 0, $executed = 0, $totalEvaluated = 0, $totalExecuted = 0)
    {
        return [
            'events'         => $events,
            'evaluated'      => $evaluated,
            'executed'       => $executed,
            'totalEvaluated' => $totalEvaluated,
            'totalExecuted'  => $totalExecuted,
        ];
    }

    /**
     * @param array $events
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockCampaignModel($events = [])
    {
        $mock = $this->getMockBuilder(CampaignModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEvents', 'setChannelFromEventProperties'])
            ->getMock()
        ;

        $mock->expects($this->any())
            ->method('getEvents')
            ->will($this->returnValue($events));

        $mock->expects($this->any())
            ->method('setChannelFromEventProperties')
            ->will($this->returnValue(null));

        return $mock;
    }

    /**
     * @param null $entity
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockEntityManager($entity = null)
    {
        $mock = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['clear', 'detach', 'getReference', 'getRepository'])
            ->getMock()
        ;

        $mock->expects($this->any())
            ->method('clear')
            ->will($this->returnValue(null));

        $mock->expects($this->any())
            ->method('detach')
            ->will($this->returnValue(null));

        $mock->expects($this->any())
            ->method('getReference')
            ->will($this->returnValue($entity));

        $mock->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue(new RepositoryMock()));

        return $mock;
    }

    /**
     * @param array $entities
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockLeadModel($entities = [])
    {
        $mock = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEntities'])
            ->getMock()
        ;

        $mock->expects($this->any())
            ->method('getEntities')
            ->will($this->returnValue($entities));

        return $mock;
    }

    /**
     * @param $classPath
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createMockClass($classPath)
    {
        return $this->getMockBuilder($classPath)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock()
            ;
    }
}
