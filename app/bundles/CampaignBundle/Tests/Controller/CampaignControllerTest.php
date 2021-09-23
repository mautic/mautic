<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\CampaignBundle\Controller\CampaignController;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\Summary;
use Mautic\CampaignBundle\Entity\SummaryRepository;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class CampaignControllerTest extends TestCase
{
    public function testGetViewArguments(): void
    {
        $campaign           = $this->mockCampaign();
        $args               = $this->mockViewArguments($campaign);
        $campaignController = $this->mockCampaignController();

        $campaignId = $campaign->getId();

        // TESTING ARGUMENTS
        $events                   = $this->getMockEvents();
        $campaignLogCounts        = $this->getMockCampaignLogCounts();
        $pendingCampaignLogCounts = $this->mockCampaignProcessedLogCounts();

        $requestMock            = $this->createMock(Request::class);
        $campaignController->setRequest($requestMock);
        $eventCollectorMock     = $this->createMock(EventCollector::class);
        $containerMock          = $this->createMock(ContainerInterface::class);
        $containerMock
            ->expects(self::at(0))
            ->method('get')
            ->with('mautic.campaign.event_collector')
            ->willReturn($eventCollectorMock);
        $formFactoryMock = $this->createMock(FormFactory::class);
        $formMock        = $this->createMock(FormInterface::class);
        $formFactoryMock
            ->expects(self::at(0))
            ->method('create')
            ->withAnyParameters()
            ->willReturn($formMock);
        $routerMock   = $this->createMock(Router::class);
        $routerMock
            ->expects(self::at(0))
            ->method('generate')
            ->withAnyParameters()
            ->willReturn('address');
        $containerMock
            ->expects(self::at(1))
            ->method('get')
            ->with('router')
            ->willReturn($routerMock);
        $containerMock
            ->expects(self::at(2))
            ->method('get')
            ->with('form.factory')
            ->willReturn($formFactoryMock);
        $modelFactoryMock  = $this->createMock(ModelFactory::class);
        $campaignModelMock = $this->createMock(CampaignModel::class);
        $modelFactoryMock
            ->expects(self::at(0))
            ->method('getModel')
            ->with('campaign')
            ->willReturn($campaignModelMock);
        $containerMock
            ->expects(self::at(3))
            ->method('get')
            ->with('mautic.model.factory')
            ->willReturn($modelFactoryMock);
        $eventRepositoryMock  = $this->createMock(EventRepository::class);
        $campaignModelMock
            ->expects(self::at(0))
            ->method('getEventRepository')
            ->willReturn($eventRepositoryMock);
        $eventRepositoryMock
            ->expects(self::at(0))
            ->method('getCampaignEvents')
            ->with($campaignId)
            ->willReturn($events);
        $coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelperMock
            ->method('get')
            ->withConsecutive(['campaign_by_range'], ['campaign_use_summary'])
            ->willReturnOnConsecutiveCalls(false, false);
        $containerMock
            ->expects(self::at(4))
            ->method('get')
            ->with('mautic.config')
            ->willReturn($coreParametersHelperMock);
        $modelFactoryMock
            ->expects(self::at(1))
            ->method('getModel')
            ->with('campaign')
            ->willReturn($campaignModelMock);
        $containerMock
            ->expects(self::at(5))
            ->method('get')
            ->with('mautic.model.factory')
            ->willReturn($modelFactoryMock);
        $containerMock
            ->expects(self::at(6))
            ->method('has')
            ->with('doctrine')
            ->willReturn(true);
        $doctrineMock = $this->createMock(ManagerRegistry::class);
        $containerMock
            ->expects(self::at(7))
            ->method('get')
            ->with('doctrine')
            ->willReturn($doctrineMock);
        $entityManagerMock = $this->createMock(EntityManager::class);
        $doctrineMock
            ->expects(self::at(0))
            ->method('getManager')
            ->willReturn($entityManagerMock);
        $leadEventLogRepositoryMock    = $this->createMock(LeadEventLogRepository::class);
        $entityManagerMock
            ->expects(self::at(0))
            ->method('getRepository')
            ->with(LeadEventLog::class)
            ->willReturn($leadEventLogRepositoryMock);
        $leadEventLogRepositoryMock
            ->expects(self::at(0))
            ->method('getCampaignLogCounts')
            ->with($campaignId, false, false, true)
            ->willReturn($campaignLogCounts);
        $leadEventLogRepositoryMock
            ->expects(self::at(1))
            ->method('getCampaignLogCounts')
            ->with($campaignId, false, false)
            ->willReturn($pendingCampaignLogCounts);
        $campaignRepositoryMock = $this->createMock(CampaignRepository::class);
        $campaignModelMock
            ->expects(self::at(1))
            ->method('getRepository')
            ->willReturn($campaignRepositoryMock);
        $campaignRepositoryMock
            ->expects(self::at(0))
            ->method('getCampaignLeadCount')
            ->with($campaignId)
            ->willReturn(8);
        $modelFactoryMock
            ->expects(self::at(2))
            ->method('getModel')
            ->with('campaign')
            ->willReturn($campaignModelMock);
        $containerMock
            ->expects(self::at(8))
            ->method('get')
            ->with('mautic.model.factory')
            ->willReturn($modelFactoryMock);
        $dateMock = $this->createMock(Form::class);
        $formMock
            ->expects(self::at(0))
            ->method('get')
            ->with('date_from')
            ->willReturn($dateMock);
        $formMock
            ->expects(self::at(1))
            ->method('get')
            ->with('date_to')
            ->willReturn($dateMock);
        $dateMock
            ->expects(self::at(0))
            ->method('getData')
            ->willReturn('Sep 14, 2020');
        $dateMock
            ->expects(self::at(1))
            ->method('getData')
            ->willReturn('Sep 16, 2020');
        $modelFactoryMock
            ->expects(self::at(3))
            ->method('getModel')
            ->with('campaign')
            ->willReturn($campaignModelMock);
        $containerMock
            ->expects(self::at(9))
            ->method('get')
            ->with('mautic.model.factory')
            ->willReturn($modelFactoryMock);
        $sessionMock = $this->createMock(Session::class);
        $containerMock
            ->expects(self::at(12))
            ->method('get')
            ->with('session')
            ->willReturn($sessionMock);
        $sessionMock
            ->expects(self::once())
            ->method('set')
            ->with('mautic.campaign.1.events.modified', []);
        $modelFactoryMock
            ->expects(self::at(4))
            ->method('getModel')
            ->with('campaign')
            ->willReturn($campaignModelMock);
        $containerMock
            ->expects(self::at(13))
            ->method('get')
            ->with('mautic.model.factory')
            ->willReturn($modelFactoryMock);
        $campaignController->setContainer($containerMock);

        $viewArguments = $campaignController->tryGetViewArguments($args, 'view');

        // RESULT
        $campaignEvents = $viewArguments['viewParameters']['campaignEvents'];
        $resultCampaign = $viewArguments['viewParameters']['campaign'];
        // Result for same campaign
        self::assertSame($campaign, $resultCampaign);
        // Condition YES percent 25%
        self::assertSame(25.0, $campaignEvents[1]['yesPercent']);
        // First action, pending 0
        self::assertSame(0, $campaignEvents[2]['logCountForPending']);
        // First action, count processed 2
        self::assertSame(2, $campaignEvents[2]['logCountProcessed']);
        // First action, YES percent 25%
        self::assertSame(25.0, $campaignEvents[2]['yesPercent']);
        // Second action, pending 1
        self::assertSame(1, $campaignEvents[3]['logCountForPending']);
        // Second action, count processed 1
        self::assertSame(1, $campaignEvents[3]['logCountProcessed']);
        // Second action, YES percent 100%
        self::assertSame(100, $campaignEvents[3]['yesPercent']);
    }

    public function testGetViewArgumentsWithSummary(): void
    {
        $campaign           = $this->mockCampaign();
        $args               = $this->mockViewArguments($campaign);
        $campaignController = $this->mockCampaignController();

        $campaignId = $campaign->getId();

        // TESTING ARGUMENTS
        $events            = $this->getMockEvents();
        $campaignLogCounts = $this->getMockSummaryCampaignLogCounts();

        $requestMock            = $this->createMock(Request::class);
        $campaignController->setRequest($requestMock);
        $eventCollectorMock     = $this->createMock(EventCollector::class);
        $containerMock          = $this->createMock(ContainerInterface::class);
        $containerMock
            ->expects(self::at(0))
            ->method('get')
            ->with('mautic.campaign.event_collector')
            ->willReturn($eventCollectorMock);
        $formFactoryMock = $this->createMock(FormFactory::class);
        $formMock        = $this->createMock(FormInterface::class);
        $formFactoryMock
            ->expects(self::at(0))
            ->method('create')
            ->withAnyParameters()
            ->willReturn($formMock);
        $routerMock   = $this->createMock(Router::class);
        $routerMock
            ->expects(self::at(0))
            ->method('generate')
            ->withAnyParameters()
            ->willReturn('address');
        $containerMock
            ->expects(self::at(1))
            ->method('get')
            ->with('router')
            ->willReturn($routerMock);
        $containerMock
            ->expects(self::at(2))
            ->method('get')
            ->with('form.factory')
            ->willReturn($formFactoryMock);
        $modelFactoryMock  = $this->createMock(ModelFactory::class);
        $campaignModelMock = $this->createMock(CampaignModel::class);
        $modelFactoryMock
            ->expects(self::at(0))
            ->method('getModel')
            ->with('campaign')
            ->willReturn($campaignModelMock);
        $containerMock
            ->expects(self::at(3))
            ->method('get')
            ->with('mautic.model.factory')
            ->willReturn($modelFactoryMock);
        $eventRepositoryMock  = $this->createMock(EventRepository::class);
        $campaignModelMock
            ->expects(self::at(0))
            ->method('getEventRepository')
            ->willReturn($eventRepositoryMock);
        $eventRepositoryMock
            ->expects(self::at(0))
            ->method('getCampaignEvents')
            ->with($campaignId)
            ->willReturn($events);
        $coreParametersHelperMock = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelperMock
            ->method('get')
            ->withConsecutive(['campaign_by_range'], ['campaign_use_summary'])
            ->willReturnOnConsecutiveCalls(false, true);
        $containerMock
            ->expects(self::at(4))
            ->method('get')
            ->with('mautic.config')
            ->willReturn($coreParametersHelperMock);
        $modelFactoryMock
            ->expects(self::at(1))
            ->method('getModel')
            ->with('campaign')
            ->willReturn($campaignModelMock);
        $containerMock
            ->expects(self::at(5))
            ->method('get')
            ->with('mautic.model.factory')
            ->willReturn($modelFactoryMock);
        $containerMock
            ->expects(self::at(6))
            ->method('has')
            ->with('doctrine')
            ->willReturn(true);
        $doctrineMock = $this->createMock(ManagerRegistry::class);
        $containerMock
            ->expects(self::at(7))
            ->method('get')
            ->with('doctrine')
            ->willReturn($doctrineMock);
        $entityManagerMock = $this->createMock(EntityManager::class);
        $doctrineMock
            ->expects(self::at(0))
            ->method('getManager')
            ->willReturn($entityManagerMock);
        $summaryRepositoryMock    = $this->createMock(SummaryRepository::class);
        $entityManagerMock
            ->expects(self::at(0))
            ->method('getRepository')
            ->with(Summary::class)
            ->willReturn($summaryRepositoryMock);
        $summaryRepositoryMock
            ->expects(self::at(0))
            ->method('getCampaignLogCounts')
            ->with($campaignId)
            ->willReturn($campaignLogCounts);
        $campaignRepositoryMock = $this->createMock(CampaignRepository::class);
        $campaignModelMock
            ->expects(self::at(1))
            ->method('getRepository')
            ->willReturn($campaignRepositoryMock);
        $campaignRepositoryMock
            ->expects(self::at(0))
            ->method('getCampaignLeadCount')
            ->with($campaignId)
            ->willReturn(8);
        $modelFactoryMock
            ->expects(self::at(2))
            ->method('getModel')
            ->with('campaign')
            ->willReturn($campaignModelMock);
        $containerMock
            ->expects(self::at(8))
            ->method('get')
            ->with('mautic.model.factory')
            ->willReturn($modelFactoryMock);
        $dateMock = $this->createMock(Form::class);
        $formMock
            ->expects(self::at(0))
            ->method('get')
            ->with('date_from')
            ->willReturn($dateMock);
        $formMock
            ->expects(self::at(1))
            ->method('get')
            ->with('date_to')
            ->willReturn($dateMock);
        $dateMock
            ->expects(self::at(0))
            ->method('getData')
            ->willReturn('Sep 14, 2020');
        $dateMock
            ->expects(self::at(1))
            ->method('getData')
            ->willReturn('Sep 16, 2020');
        $modelFactoryMock
            ->expects(self::at(3))
            ->method('getModel')
            ->with('campaign')
            ->willReturn($campaignModelMock);
        $containerMock
            ->expects(self::at(9))
            ->method('get')
            ->with('mautic.model.factory')
            ->willReturn($modelFactoryMock);
        $sessionMock = $this->createMock(Session::class);
        $containerMock
            ->expects(self::at(12))
            ->method('get')
            ->with('session')
            ->willReturn($sessionMock);
        $sessionMock
            ->expects(self::once())
            ->method('set')
            ->with('mautic.campaign.1.events.modified', []);
        $modelFactoryMock
            ->expects(self::at(4))
            ->method('getModel')
            ->with('campaign')
            ->willReturn($campaignModelMock);
        $containerMock
            ->expects(self::at(13))
            ->method('get')
            ->with('mautic.model.factory')
            ->willReturn($modelFactoryMock);
        $campaignController->setContainer($containerMock);

        $viewArguments = $campaignController->tryGetViewArguments($args, 'view');

        // RESULT
        $campaignEvents = $viewArguments['viewParameters']['campaignEvents'];
        $resultCampaign = $viewArguments['viewParameters']['campaign'];

        // Result for same campaign
        self::assertSame($campaign, $resultCampaign);
        // Condition YES percent 25%
        self::assertSame(25.0, $campaignEvents[1]['yesPercent']);
        // First action, pending 0
        self::assertSame(0, $campaignEvents[2]['logCountForPending']);
        // First action, count processed 2
        self::assertSame(2, $campaignEvents[2]['logCountProcessed']);
        // First action, YES percent 25%
        self::assertSame(25.0, $campaignEvents[2]['yesPercent']);
        // Second action, pending 1
        self::assertSame(0, $campaignEvents[3]['logCountForPending']);
        // Second action, count processed 1
        self::assertSame(2, $campaignEvents[3]['logCountProcessed']);
        // Second action, YES percent 100%
        self::assertSame(100, $campaignEvents[3]['yesPercent']);
    }

    private function mockCampaign(int $id = 10): Campaign
    {
        return new class($id) extends Campaign {
            private $id;

            public function __construct(int $id)
            {
                $this->id = $id;
                parent::__construct();
            }

            public function getId(): int
            {
                return $this->id;
            }
        };
    }

    private function mockViewArguments(Campaign $campaign): array
    {
        $args['entity']         = $campaign;
        $args['objectId']       = 1;
        $args['viewParameters'] = [];

        return $args;
    }

    private function mockCampaignController(): CampaignController
    {
        // Anonymous class that extends CampaignController to be able to test protected function
        return new class() extends CampaignController {
            public function tryGetViewArguments(array $args, string $action): array
            {
                return $this->getViewArguments($args, $action);
            }
        };
    }

    private function getMockEvents(): array
    {
        return [
            1 => [
                'id'           => 1,
                'name'         => 'Condition',
                'eventType'    => 'condition',
                'decisionPath' => null,
                'parent_id'    => null,
            ],
            2 => [
                'id'           => 2,
                'name'         => 'Action 1',
                'eventType'    => 'action',
                'decisionPath' => 'yes',
                'parent_id'    => 1,
            ],
            3 => [
                'id'        => 3,
                'name'      => 'Action 2',
                'eventType' => 'action',
                'parent_id' => 2,
            ],
        ];
    }

    private function getMockCampaignLogCounts(): array
    {
        return [
            1 => [
                0 => 6,
                1 => 2,
            ],
            2 => [
                0 => 0,
                1 => 2,
            ],
            3 => [
                0 => 0,
                1 => 2,
            ],
        ];
    }

    private function mockCampaignProcessedLogCounts(): array
    {
        return [
            1 => [
                0 => 6,
                1 => 2,
            ],
            2 => [
                0 => 0,
                1 => 2,
            ],
            3 => [
                0 => 0,
                1 => 1,
            ],
        ];
    }

    private function getMockSummaryCampaignLogCounts(): array
    {
        return [
            1 => [
                0 => 6,
                1 => 2,
                2 => 8,
            ],
            2 => [
                0 => 0,
                1 => 2,
                2 => 2,
            ],
            3 => [
                0 => 0,
                1 => 2,
                2 => 2,
            ],
        ];
    }
}
