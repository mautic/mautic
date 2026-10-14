<?php

declare(strict_types=1);

namespace Mautic\DashboardBundle\Tests\Dashboard;

use Mautic\CoreBundle\Helper\UserHelper;

use Mautic\CoreBundle\Test\ReflectionHelper;
use Mautic\DashboardBundle\Dashboard\Widget;
use Mautic\DashboardBundle\Entity\Widget as WidgetEntity;
use Mautic\DashboardBundle\Model\DashboardModel;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[AllowMockObjectsWithoutExpectations]
final class WidgetTest extends TestCase
{
    private const int USER_ID = 1;

    /**
     * @var MockObject&DashboardModel
     */
    private MockObject $dashboardModel;

    /**
     * @var MockObject&UserHelper
     */
    private MockObject $userHelper;

    private User $user;

    private Widget $widget;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dashboardModel = $this->createMock(DashboardModel::class);
        $this->userHelper     = $this->createMock(UserHelper::class);

        $this->user = new User();
        ReflectionHelper::setEntityId($this->user, self::USER_ID);

        $this->widget = new Widget(
            $this->dashboardModel,
            $this->userHelper,
            $this->createStub(RequestStack::class)
        );
    }

    public function testGetSuccess(): void
    {
        $widgetId = 2;
        $widget   = new WidgetEntity();
        ReflectionHelper::setEntityId($widget, $widgetId);
        $widget->setCreatedBy(self::USER_ID);
        $filter = [
            'dateFrom' => new \DateTime(),
            'dateTo'   => new \DateTime(),
        ];

        $this->dashboardModel->expects($this->once())
            ->method('getEntity')
            ->with($widgetId)
            ->willReturn($widget);

        $this->userHelper->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);

        $this->dashboardModel->expects($this->once())
            ->method('getDefaultFilter')
            ->willReturn($filter);

        $this->dashboardModel->expects($this->once())
            ->method('populateWidgetContent')
            ->with($widget, $filter);

        $this->widget->get($widgetId);
    }

    public function testGetNotFoundHttpException(): void
    {
        $widgetId = 2;
        $widget   = null;

        $this->dashboardModel->expects($this->once())
            ->method('getEntity')
            ->with($widgetId)
            ->willReturn($widget);

        $this->expectException(NotFoundHttpException::class);
        $this->widget->get($widgetId);
    }

    public function testGetNotFoundHttpExceptionEmptyEntity(): void
    {
        $widgetId = 2;
        $widget   = new WidgetEntity();
        $widget->setCreatedBy(self::USER_ID);

        $this->dashboardModel->expects($this->once())
            ->method('getEntity')
            ->with($widgetId)
            ->willReturn($widget);

        $this->expectException(NotFoundHttpException::class);
        $this->widget->get($widgetId);
    }

    public function testGetAccessDeniedException(): void
    {
        $widgetId = 2;
        $widget   = new WidgetEntity();
        ReflectionHelper::setEntityId($widget, $widgetId);
        $widget->setCreatedBy(self::USER_ID + 1);

        $this->dashboardModel->expects($this->once())
            ->method('getEntity')
            ->with($widgetId)
            ->willReturn($widget);

        $this->userHelper->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);

        $this->expectException(AccessDeniedException::class);
        $this->widget->get($widgetId);
    }
}
