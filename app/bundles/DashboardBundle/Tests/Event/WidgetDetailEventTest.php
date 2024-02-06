<?php

namespace Mautic\DashboardBundle\Tests\Entity;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use PHPUnit\Framework\MockObject\MockObject;

class WidgetDetailEventTest extends \PHPUnit\Framework\TestCase
{
    private WidgetDetailEvent $widgetDetailEvent;
    private MockObject $translator;
    private MockObject $security;
    private MockObject $widget;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator            = $this->createMock(Translator::class);
        $this->security              = $this->createMock(CorePermissions::class);
        $this->widget                = $this->createMock(Widget::class);

        $this->widgetDetailEvent = new WidgetDetailEvent(
            $this->translator,
            $this->security,
            $this->widget
        );
    }

    public function testGetCacheKey(): void
    {
        $this->widget
            ->method('getParams')
            ->willReturn(['dateFrom' => [], 'dateTo' => []]);

        $this->translator->expects($this->once())
            ->method('getLocale')
            ->willReturn('en');
        $this->assertStringContainsString('dashboard.widget.', $this->widgetDetailEvent->getCacheKey());
    }
}
