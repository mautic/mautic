<?php

namespace Mautic\DashboardBundle\Tests\Event;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\DashboardBundle\Entity\Widget;
use Mautic\DashboardBundle\Event\WidgetDetailEvent;
use PHPUnit\Framework\MockObject\MockObject;

final class WidgetDetailEventTest extends \PHPUnit\Framework\TestCase
{
    private WidgetDetailEvent $widgetDetailEvent;

    /**
     * @var MockObject&Translator
     */
    private MockObject $translator;

    /**
     * @var MockObject&Widget
     */
    private MockObject $widget;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator            = $this->createMock(Translator::class);
        $security                    = $this->createMock(CorePermissions::class);
        $this->widget                = $this->createMock(Widget::class);

        $this->widgetDetailEvent = new WidgetDetailEvent(
            $this->translator,
            $security,
            $this->widget
        );
    }

    public function testGetCacheKey(): void
    {
        $this->widget
            ->method('getParams')
            ->willReturn(['dateFrom' => '', 'dateTo' => '']);

        $this->translator->expects($this->once())
            ->method('getLocale')
            ->willReturn('en');
        $this->assertStringContainsString('dashboard.widget.', $this->widgetDetailEvent->getCacheKey());
    }
}
