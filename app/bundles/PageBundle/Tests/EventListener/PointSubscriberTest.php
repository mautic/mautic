<?php

namespace Mautic\PageBundle\Tests\EventListener;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageHitEvent;
use Mautic\PageBundle\EventListener\PointSubscriber;
use Mautic\PageBundle\Helper\PointActionHelper;
use Mautic\PointBundle\Event\PointBuilderEvent;
use Mautic\PointBundle\Model\PointModel;
use PHPUnit\Framework\TestCase;

class PointSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        self::assertEquals(
            [
                'mautic.point_on_build' => ['onPointBuild', 0],
                'mautic.page_on_hit'    => ['onPageHit', 0],
            ],
            PointSubscriber::getSubscribedEvents()
        );
    }

    public function testPointBuildAddsActions(): void
    {
        $pointModel        = $this->createMock(PointModel::class);
        $pointBuilderEvent = $this->createMock(PointBuilderEvent::class);
        $pointActionHelper = $this->createMock(PointActionHelper::class);

        $pointBuilderEvent->expects(self::exactly(2))->method('addAction')->withConsecutive(
            [
                'page.hit',
                [
                    'group'       => 'mautic.page.point.action',
                    'label'       => 'mautic.page.point.action.pagehit',
                    'description' => 'mautic.page.point.action.pagehit_descr',
                    'callback'    => [PointActionHelper::class, 'validatePageHit'],
                    'formType'    => \Mautic\PageBundle\Form\Type\PointActionPageHitType::class,
                ],
            ],
            [
                'url.hit',
                [
                    'group'       => 'mautic.page.point.action',
                    'label'       => 'mautic.page.point.action.urlhit',
                    'description' => 'mautic.page.point.action.urlhit_descr',
                    'callback'    => [$pointActionHelper, 'validateUrlHit'],
                    'formType'    => \Mautic\PageBundle\Form\Type\PointActionUrlHitType::class,
                    'formTheme'   => '@MauticPage/FormTheme/Point/pointaction_urlhit_widget.html.twig',
                ],
            ]
        );

        $pointSubscriber = new PointSubscriber($pointModel, $pointActionHelper);
        $pointSubscriber->onPointBuild($pointBuilderEvent);
    }

    public function testPageHitTriggersPageHitWhenPageIsSet(): void
    {
        $pageHitEvent      = $this->createMock(PageHitEvent::class);
        $page              = $this->createMock(Page::class);
        $hit               = $this->createMock(Hit::class);
        $lead              = $this->createMock(Lead::class);
        $pointModel        = $this->createMock(PointModel::class);
        $pointActionHelper = $this->createMock(PointActionHelper::class);

        $pageHitEvent->expects(self::once())->method('getPage')->willReturn($page);
        $pageHitEvent->expects(self::once())->method('getHit')->willReturn($hit);
        $pageHitEvent->expects(self::once())->method('getLead')->willReturn($lead);
        $pointModel->expects(self::once())->method('triggerAction')->with('page.hit', $hit, null, $lead);

        $pointSubscriber = new PointSubscriber($pointModel, $pointActionHelper);
        $pointSubscriber->onPageHit($pageHitEvent);
    }

    public function testURLHitTriggersPageHitWhenPageIsSet(): void
    {
        $pageHitEvent      = $this->createMock(PageHitEvent::class);
        $hit               = $this->createMock(Hit::class);
        $lead              = $this->createMock(Lead::class);
        $pointModel        = $this->createMock(PointModel::class);
        $pointActionHelper = $this->createMock(PointActionHelper::class);

        $pageHitEvent->expects(self::once())->method('getPage')->willReturn(null);
        $pageHitEvent->expects(self::once())->method('getHit')->willReturn($hit);
        $pageHitEvent->expects(self::once())->method('getLead')->willReturn($lead);
        $pointModel->expects(self::once())->method('triggerAction')->with('url.hit', $hit, null, $lead);

        $pointSubscriber = new PointSubscriber($pointModel, $pointActionHelper);
        $pointSubscriber->onPageHit($pageHitEvent);
    }
}
