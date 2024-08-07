<?php

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\LeadBundle\EventListener\DashboardSubscriber;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DateHelperStub
{
    public function formatRange(\DateInterval $interval)
    {
        return $interval->format('%H:%I:%S');
    }
}

class DashboardSubscriberTest extends TestCase
{
    private $leadModel;
    private $leadListModel;
    private $router;
    private $translator;
    private $dateHelper;
    private $dashboardSubscriber;

    protected function setUp(): void
    {
        $this->leadModel     = $this->createMock(LeadModel::class);
        $this->leadListModel = $this->createMock(ListModel::class);
        $this->router        = $this->createMock(RouterInterface::class);
        $this->translator    = $this->createMock(TranslatorInterface::class);
        $this->dateHelper    = new DateHelperStub();

        $this->dashboardSubscriber = new DashboardSubscriber(
            $this->leadModel,
            $this->leadListModel,
            $this->router,
            $this->translator,
            $this->dateHelper
        );
    }

    public function testOnWidgetDetailGenerateCreatedLeadsInTime(): void
    {
        $widget = $this->createMock(\Mautic\DashboardBundle\Entity\Widget::class);
        $event  = $this->createMock(WidgetDetailEvent::class);

        $widget->method('getParams')->willReturn([
            'timeUnit'   => 'day',
            'dateFrom'   => new \DateTime('-7 days'),
            'dateTo'     => new \DateTime(),
            'dateFormat' => 'Y-m-d',
            'filter'     => [],
        ]);

        $event->method('getType')->willReturn('created.leads.in.time');
        $event->method('getWidget')->willReturn($widget);
        $event->method('hasPermission')->willReturn(true);
        $event->method('isCached')->willReturn(false);

        $this->leadModel->expects($this->exactly(2))
            ->method('getLeadsLineChartData')
            ->willReturn([
                'datasets' => [
                    [
                        'data' => [10, 20, 30, 40, 50, 60, 70],
                    ],
                ],
            ]);

        $event->expects($this->once())
            ->method('setTemplate')
            ->with('@MauticLead/Widget/created_leads_in_time.html.twig');

        $event->expects($this->once())
            ->method('setTemplateData')
            ->with($this->arrayHasKey('chartData'));

        $event->expects($this->once())
            ->method('stopPropagation');

        $this->dashboardSubscriber->onWidgetDetailGenerate($event);
    }

    public function testOnWidgetDetailGenerateAnonymousVsIdentifiedLeads(): void
    {
        $widget = $this->createMock(\Mautic\DashboardBundle\Entity\Widget::class);
        $event  = $this->createMock(WidgetDetailEvent::class);

        $widget->method('getParams')->willReturn([
            'dateFrom' => new \DateTime('-7 days'),
            'dateTo'   => new \DateTime(),
        ]);

        $event->method('getType')->willReturn('anonymous.vs.identified.leads');
        $event->method('getWidget')->willReturn($widget);
        $event->method('hasPermission')->willReturn(true);
        $event->method('isCached')->willReturn(false);

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

        $event->expects($this->once())
            ->method('stopPropagation');

        $this->dashboardSubscriber->onWidgetDetailGenerate($event);
    }

    public function testOnWidgetDetailGenerateMapOfLeads(): void
    {
        $widget = $this->createMock(\Mautic\DashboardBundle\Entity\Widget::class);
        $event  = $this->createMock(WidgetDetailEvent::class);

        $widget->method('getParams')->willReturn([
            'dateFrom' => new \DateTime('-7 days'),
            'dateTo'   => new \DateTime(),
        ]);

        $event->method('getType')->willReturn('map.of.leads');
        $event->method('getWidget')->willReturn($widget);
        $event->method('hasPermission')->willReturn(true);
        $event->method('isCached')->willReturn(false);

        $this->leadModel->expects($this->once())
            ->method('getLeadMapData')
            ->willReturn(['data' => [/* map data */]]);

        $event->expects($this->once())
            ->method('setTemplate')
            ->with('@MauticCore/Helper/map.html.twig');

        $event->expects($this->once())
            ->method('setTemplateData')
            ->with($this->arrayHasKey('data'));

        $event->expects($this->once())
            ->method('stopPropagation');

        $this->dashboardSubscriber->onWidgetDetailGenerate($event);
    }

    public function testOnWidgetDetailGenerateTopLists(): void
    {
        $widget = $this->createMock(\Mautic\DashboardBundle\Entity\Widget::class);
        $event  = $this->createMock(WidgetDetailEvent::class);

        $widget->method('getParams')->willReturn([
            'dateFrom' => new \DateTime('-7 days'),
            'dateTo'   => new \DateTime(),
            'limit'    => 5,
        ]);

        $event->method('getType')->willReturn('top.lists');
        $event->method('getWidget')->willReturn($widget);
        $event->method('hasPermission')->willReturn(true);
        $event->method('isCached')->willReturn(false);

        $this->leadListModel->expects($this->once())
            ->method('getTopLists')
            ->willReturn([['id' => 1, 'name' => 'List 1', 'alias' => 'list1', 'leads' => 50]]);

        $event->expects($this->once())
            ->method('setTemplate')
            ->with('@MauticCore/Helper/table.html.twig');

        $event->expects($this->once())
            ->method('setTemplateData')
            ->with($this->arrayHasKey('bodyItems'));

        $event->expects($this->once())
            ->method('stopPropagation');

        $this->dashboardSubscriber->onWidgetDetailGenerate($event);
    }

    public function testOnWidgetDetailGenerateLeadLifetime(): void
    {
        $widget = $this->createMock(\Mautic\DashboardBundle\Entity\Widget::class);
        $event  = $this->createMock(WidgetDetailEvent::class);

        $widget->method('getParams')->willReturn([
            'dateFrom' => new \DateTime('-7 days'),
            'dateTo'   => new \DateTime(),
            'limit'    => 5,
            'filter'   => ['flag' => []],
        ]);

        $event->method('getType')->willReturn('lead.lifetime');
        $event->method('getWidget')->willReturn($widget);
        $event->method('hasPermission')->willReturn(true);

        $this->leadListModel->expects($this->once())
            ->method('getLifeCycleSegments')
            ->willReturn([['name' => 'Segment 1', 'leads' => 100, 'alias' => 'segment1', 'id' => 1]]);

        $this->leadListModel->expects($this->once())
            ->method('getLifeCycleSegmentChartData')
            ->willReturn(['chartData' => [/* chart data */]]);

        $event->expects($this->once())
            ->method('setTemplate')
            ->with('@MauticCore/Helper/lifecycle.html.twig');

        $event->expects($this->once())
            ->method('setTemplateData')
            ->with($this->arrayHasKey('chartItems'));

        $event->expects($this->once())
            ->method('stopPropagation');

        $this->dashboardSubscriber->onWidgetDetailGenerate($event);
    }

    public function testOnWidgetDetailGenerateTopOwners(): void
    {
        $widget = $this->createMock(\Mautic\DashboardBundle\Entity\Widget::class);
        $event  = $this->createMock(WidgetDetailEvent::class);

        $widget->method('getParams')->willReturn([
            'dateFrom' => new \DateTime('-7 days'),
            'dateTo'   => new \DateTime(),
            'limit'    => 5,
        ]);

        $event->method('getType')->willReturn('top.owners');
        $event->method('getWidget')->willReturn($widget);
        $event->method('hasPermission')->willReturn(true);
        $event->method('isCached')->willReturn(false);

        $this->leadModel->expects($this->once())
            ->method('getTopOwners')
            ->willReturn([['id' => 1, 'name' => 'Owner 1', 'leads' => 50]]);

        $event->expects($this->once())
            ->method('setTemplate')
            ->with('@MauticCore/Helper/table.html.twig');

        $event->expects($this->once())
            ->method('setTemplateData')
            ->with($this->arrayHasKey('bodyItems'));

        $event->expects($this->once())
            ->method('stopPropagation');

        $this->dashboardSubscriber->onWidgetDetailGenerate($event);
    }

    public function testOnWidgetDetailGenerateTopCreators(): void
    {
        $widget        = $this->createMock(\Mautic\DashboardBundle\Entity\Widget::class);
        $event         = $this->createMock(WidgetDetailEvent::class);
        $canViewOthers = true;

        $widget->method('getParams')->willReturn([
            'dateFrom' => new \DateTime('-7 days'),
            'dateTo'   => new \DateTime(),
            'limit'    => 5,
        ]);

        $event->method('getType')->willReturn('top.creators');
        $event->method('getWidget')->willReturn($widget);
        $event->method('hasPermission')->willReturn(true);
        $event->method('isCached')->willReturn(false);

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

        $event->expects($this->once())
            ->method('stopPropagation');

        $this->dashboardSubscriber->onWidgetDetailGenerate($event);
    }

    public function testOnWidgetDetailGenerateCreatedLeads(): void
    {
        $widget        = $this->createMock(\Mautic\DashboardBundle\Entity\Widget::class);
        $event         = $this->createMock(WidgetDetailEvent::class);
        $canViewOthers = true;

        $widget->method('getParams')->willReturn([
            'dateFrom' => new \DateTime('-7 days'),
            'dateTo'   => new \DateTime(),
            'limit'    => 5,
        ]);

        $event->method('getType')->willReturn('created.leads');
        $event->method('getWidget')->willReturn($widget);
        $event->method('hasPermission')->willReturn(true);
        $event->method('isCached')->willReturn(false);

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

        $event->expects($this->once())
            ->method('stopPropagation');

        $this->dashboardSubscriber->onWidgetDetailGenerate($event);
    }

    public function testOnWidgetDetailGenerateSegmentsBuildTime(): void
    {
        $widget        = $this->createMock(\Mautic\DashboardBundle\Entity\Widget::class);
        $event         = $this->createMock(WidgetDetailEvent::class);
        $canViewOthers = true;

        $widget->method('getParams')->willReturn([
            'dateFrom' => new \DateTime('-7 days'),
            'dateTo'   => new \DateTime(),
            'limit'    => 5,
            'order'    => 'desc',
            'segments' => [],
        ]);

        $event->method('getType')->willReturn('segments.build.time');
        $event->method('getWidget')->willReturn($widget);
        $event->method('hasPermission')->willReturn(true);
        $event->method('isCached')->willReturn(false);

        $this->leadListModel->expects($this->once())
            ->method('getSegmentsBuildTime')
            ->willReturn([
                $this->createConfiguredMock(\Mautic\LeadBundle\Entity\LeadList::class, [
                    'getId'            => 1,
                    'getName'          => 'Segment 1',
                    'getCreatedByUser' => 'User 1',
                    'getLastBuiltTime' => 3600,
                ]),
            ]);

        $this->router->expects($this->once())
            ->method('generate')
            ->with('mautic_segment_action', ['objectAction' => 'view', 'objectId' => 1])
            ->willReturn('/segment/view/1');

        $this->dateHelper->expects($this->once())
            ->method('formatRange')
            ->willReturn('01:00:00');

        $event->expects($this->once())
            ->method('setTemplate')
            ->with('@MauticCore/Helper/table.html.twig');

        $event->expects($this->once())
            ->method('setTemplateData')
            ->with($this->callback(function ($data) {
                return isset($data['headItems'], $data['bodyItems'], $data['raw']);
            }));

        $event->expects($this->once())
            ->method('stopPropagation');

        $this->dashboardSubscriber->onWidgetDetailGenerate($event);
    }
}
