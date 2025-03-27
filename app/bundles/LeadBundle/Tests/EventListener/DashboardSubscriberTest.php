<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\LeadBundle\EventListener\DashboardSubscriber;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardSubscriberTest extends TestCase
{
    private LeadModel $leadModel;
    private ListModel $leadListModel;
    private RouterInterface $router;
    private TranslatorInterface $translator;
    private DateHelper $dateHelper;
    private DashboardSubscriber $dashboardSubscriber;

    protected function setUp(): void
    {
        $this->leadModel     = $this->createMock(LeadModel::class);
        $this->leadListModel = $this->createMock(ListModel::class);
        $this->router        = $this->createMock(RouterInterface::class);
        $this->translator    = $this->createMock(TranslatorInterface::class);

        // Set up translator with callback for both parent class and DateHelper
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id, array $parameters = [], $domain = null) {
                if (empty($parameters)) {
                    return $id;
                }

                return $id.' '.json_encode($parameters);
            });

        // Create CoreParametersHelper mock
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);

        // Create DateHelper with the translator
        $this->dateHelper = new DateHelper(
            'F j, Y g:i a T', // dateFormat full
            'D, M d',         // dateFormat short
            'F j, Y',         // dateFormat dateonly
            'g:i a',          // dateFormat timeonly
            $this->translator,
            $coreParametersHelper
        );

        // Create subscriber with the same translator instance
        $this->dashboardSubscriber = new DashboardSubscriber(
            $this->leadModel,
            $this->leadListModel,
            $this->router,
            $this->translator,
            $this->dateHelper
        );
    }

    private function createEvent(string $type, array $params = []): WidgetDetailEvent
    {
        $event  = $this->createMock(WidgetDetailEvent::class);
        $widget = $this->createMock(\Mautic\DashboardBundle\Entity\Widget::class);

        $widget->method('getParams')->willReturn($params);
        $event->method('getType')->willReturn($type);
        $event->method('getWidget')->willReturn($widget);
        $event->method('hasPermission')->willReturn(true);
        $event->method('isCached')->willReturn(false);
        $event->method('getTranslator')->willReturn($this->translator);

        // Allow stopPropagation to be called multiple times
        $event->expects($this->atLeastOnce())
            ->method('stopPropagation');

        return $event;
    }

    public function testOnWidgetDetailGenerateCreatedLeadsInTime(): void
    {
        $params = [
            'timeUnit'   => 'day',
            'dateFrom'   => new \DateTime('-7 days'),
            'dateTo'     => new \DateTime(),
            'dateFormat' => 'Y-m-d',
            'filter'     => [],
        ];

        $event = $this->createEvent('created.leads.in.time', $params);

        $chartData = [
            'labels'   => ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'],
            'datasets' => [
                [
                    'data' => [10, 20, 30, 40, 50, 60, 70],
                ],
            ],
        ];

        $this->leadModel->expects($this->exactly(2))
            ->method('getLeadsLineChartData')
            ->willReturn($chartData);

        $event->expects($this->once())
            ->method('setTemplate')
            ->with('@MauticLead/Widget/created_leads_in_time.html.twig');

        $event->expects($this->once())
            ->method('setTemplateData')
            ->with($this->arrayHasKey('chartData'));

        $this->dashboardSubscriber->onWidgetDetailGenerate($event);
    }

    public function testOnWidgetDetailGenerateAnonymousVsIdentifiedLeads(): void
    {
        $params = [
            'dateFrom' => new \DateTime('-7 days'),
            'dateTo'   => new \DateTime(),
        ];

        $event = $this->createEvent('anonymous.vs.identified.leads', $params);

        $this->leadModel->expects($this->once())
            ->method('getAnonymousVsIdentifiedPieChartData')
            ->willReturn([
                'datasets' => [
                    [
                        'data' => [60, 40],
                    ],
                ],
            ]);

        $event->expects($this->once())
            ->method('setTemplate')
            ->with('@MauticCore/Helper/chart.html.twig');

        $event->expects($this->once())
            ->method('setTemplateData')
            ->with($this->arrayHasKey('chartData'));

        $this->dashboardSubscriber->onWidgetDetailGenerate($event);
    }

    public function testOnWidgetDetailGenerateMapOfLeads(): void
    {
        $params = [
            'dateFrom' => new \DateTime('-7 days'),
            'dateTo'   => new \DateTime(),
        ];

        $event = $this->createEvent('map.of.leads', $params);

        $this->leadModel->expects($this->once())
            ->method('getLeadMapData')
            ->willReturn(['data' => [/* map data */]]);

        $event->expects($this->once())
            ->method('setTemplate')
            ->with('@MauticCore/Helper/map.html.twig');

        $event->expects($this->once())
            ->method('setTemplateData')
            ->with($this->arrayHasKey('data'));

        $this->dashboardSubscriber->onWidgetDetailGenerate($event);
    }

    public function testOnWidgetDetailGenerateTopLists(): void
    {
        $params = [
            'dateFrom' => new \DateTime('-7 days'),
            'dateTo'   => new \DateTime(),
            'limit'    => 5,
        ];

        $event = $this->createEvent('top.lists', $params);

        $this->leadListModel->expects($this->once())
            ->method('getTopLists')
            ->willReturn([['id' => 1, 'name' => 'List 1', 'alias' => 'list1', 'leads' => 50]]);

        $event->expects($this->once())
            ->method('setTemplate')
            ->with('@MauticCore/Helper/table.html.twig');

        $event->expects($this->once())
            ->method('setTemplateData')
            ->with($this->arrayHasKey('bodyItems'));

        $this->dashboardSubscriber->onWidgetDetailGenerate($event);
    }

    public function testOnWidgetDetailGenerateLeadLifetime(): void
    {
        $params = [
            'timeUnit'   => 'day',
            'dateFrom'   => new \DateTime('-7 days'),
            'dateTo'     => new \DateTime(),
            'dateFormat' => 'Y-m-d',
            'limit'      => 5,
            'filter'     => ['flag' => []],
        ];

        $event = $this->createEvent('lead.lifetime', $params);

        $this->leadListModel->expects($this->once())
            ->method('getLifeCycleSegments')
            ->willReturn([['name' => 'Segment 1', 'leads' => 100, 'alias' => 'segment1', 'id' => 1]]);

        $this->leadListModel->expects($this->once())
            ->method('getLifeCycleSegmentChartData')
            ->willReturn([
                'labels'   => ['Stage 1', 'Stage 2'],
                'datasets' => [[
                    'data' => [50, 50],
                ]],
            ]);

        $event->expects($this->once())
            ->method('setTemplate')
            ->with('@MauticCore/Helper/lifecycle.html.twig');

        $event->expects($this->once())
            ->method('setTemplateData')
            ->with($this->arrayHasKey('chartItems'));

        $this->dashboardSubscriber->onWidgetDetailGenerate($event);
    }

    public function testOnWidgetDetailGenerateTopOwners(): void
    {
        $params = [
            'dateFrom' => new \DateTime('-7 days'),
            'dateTo'   => new \DateTime(),
            'limit'    => 5,
        ];

        $event = $this->createEvent('top.owners', $params);

        $this->leadModel->expects($this->once())
            ->method('getTopOwners')
            ->willReturn([[
                'owner_id'   => 1,
                'first_name' => 'John',
                'last_name'  => 'Doe',
                'owner_name' => 'John Doe',
                'leads'      => 50,
            ]]);

        $event->expects($this->once())
            ->method('setTemplate')
            ->with('@MauticCore/Helper/table.html.twig');

        $event->expects($this->once())
            ->method('setTemplateData')
            ->with($this->arrayHasKey('bodyItems'));

        $this->dashboardSubscriber->onWidgetDetailGenerate($event);
    }

    public function testOnWidgetDetailGenerateTopCreators(): void
    {
        $params = [
            'dateFrom' => new \DateTime('-7 days'),
            'dateTo'   => new \DateTime(),
            'limit'    => 5,
        ];

        $event = $this->createEvent('top.creators', $params);

        $this->leadModel->expects($this->once())
            ->method('getTopCreators')
            ->willReturn([
                ['created_by' => 1, 'created_by_user' => 'User 1', 'leads' => 50],
            ]);

        $this->router->expects($this->once())
            ->method('generate')
            ->with('mautic_user_action', ['objectAction' => 'edit', 'objectId' => 1])
            ->willReturn('/user/edit/1');

        $event->expects($this->once())
            ->method('setTemplate')
            ->with('@MauticCore/Helper/table.html.twig');

        $event->expects($this->once())
            ->method('setTemplateData')
            ->with($this->callback(function ($data) {
                return isset($data['headItems'], $data['bodyItems'], $data['raw']);
            }));

        $this->dashboardSubscriber->onWidgetDetailGenerate($event);
    }

    public function testOnWidgetDetailGenerateCreatedLeads(): void
    {
        $params = [
            'dateFrom' => new \DateTime('-7 days'),
            'dateTo'   => new \DateTime(),
            'limit'    => 5,
        ];

        $event = $this->createEvent('created.leads', $params);

        $this->leadModel->expects($this->once())
            ->method('getLeadList')
            ->willReturn([
                ['id' => 1, 'name' => 'Lead 1'],
            ]);

        $this->router->expects($this->once())
            ->method('generate')
            ->with('mautic_contact_action', ['objectAction' => 'view', 'objectId' => 1])
            ->willReturn('/contact/view/1');

        $event->expects($this->once())
            ->method('setTemplate')
            ->with('@MauticCore/Helper/table.html.twig');

        $event->expects($this->once())
            ->method('setTemplateData')
            ->with($this->callback(function ($data) {
                return isset($data['headItems'], $data['bodyItems'], $data['raw']);
            }));

        $this->dashboardSubscriber->onWidgetDetailGenerate($event);
    }

    public function testOnWidgetDetailGenerateSegmentsBuildTime(): void
    {
        $params = [
            'dateFrom' => new \DateTime('-7 days'),
            'dateTo'   => new \DateTime(),
            'limit'    => 5,
            'order'    => 'desc',
            'segments' => [],
        ];

        $event = $this->createEvent('segments.build.time', $params);

        $segment = $this->createMock(\Mautic\LeadBundle\Entity\LeadList::class);
        $segment->method('getId')->willReturn(1);
        $segment->method('getName')->willReturn('Segment 1');
        $segment->method('getCreatedByUser')->willReturn('User 1');
        $segment->method('getLastBuiltTime')->willReturn(3600.0);

        $this->leadListModel->expects($this->once())
            ->method('getSegmentsBuildTime')
            ->willReturn([$segment]);

        $this->router->expects($this->once())
            ->method('generate')
            ->with('mautic_segment_action', ['objectAction' => 'view', 'objectId' => 1])
            ->willReturn('/segment/view/1');

        $event->expects($this->once())
            ->method('setTemplate')
            ->with('@MauticCore/Helper/table.html.twig');

        $event->expects($this->once())
            ->method('setTemplateData')
            ->with($this->callback(function ($data) {
                return isset($data['headItems'], $data['bodyItems'], $data['raw']);
            }));

        $this->dashboardSubscriber->onWidgetDetailGenerate($event);
    }
}
