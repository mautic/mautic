<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Tests\EventListener;

use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use Mautic\PageBundle\EventListener\DashboardBestTrackingPagesSubscriber;
use Mautic\PageBundle\Model\PageModel;
use Mautic\PageBundle\Tests\Model\PageModelUnitTest;
use Symfony\Component\Translation\TranslatorInterface;

class DashboardBestTrackingPagesSubscriberTest extends \PHPUnit\Framework\TestCase
{
    public function testGetPopularTrackedPagesWidget()
    {
        $pageModel = $this->createMock(PageModel::class);
        $pageModel->expects($this->once())->method('getPopularTrackedPages')->willReturn(PageModelUnitTest::POPULAR_TRACKED_PAGES_QUERY_RESPONSE);
        $dashboardBestTrackingPagesSubscriber = new DashboardBestTrackingPagesSubscriber($pageModel);
        $widgetDetailEvent                    = $this->createMock(WidgetDetailEvent::class);
        $widgetDetailEvent->expects($this->once())->method('isCached')->willReturn(false);
        $widgetDetailEvent->expects($this->once())->method('getType')->willReturn('best.tracking.pages');
        $widget = new Widget();
        $params = ['dateFrom' => new \DateTime(), 'dateTo' => new \DateTime()];
        $widget->setParams($params);
        $widgetDetailEvent->expects($this->once())->method('getWidget')->willReturn($widget);
        $widgetDetailEvent->expects($this->once())->method('setTemplateData')->willReturnCallback(function ($data) {
            $this->assertCount(2, $data['bodyItems']);
            $this->assertEquals('Page 1', $data['bodyItems'][0][0]['value']);
            $this->assertEquals(2, $data['bodyItems'][0][1]['value']);
            $this->assertEquals('Page 2', $data['bodyItems'][1][0]['value']);
            $this->assertEquals(1, $data['bodyItems'][1][1]['value']);
        });
        $dashboardBestTrackingPagesSubscriber->onWidgetDetailGenerate($widgetDetailEvent);
    }
}
