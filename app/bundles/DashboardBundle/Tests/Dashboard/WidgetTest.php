<?php

declare(strict_types=1);

namespace Mautic\DashboardBundle\Tests\Dashboard;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\DashboardBundle\Dashboard\Widget;
use Mautic\DashboardBundle\Entity\Widget as WidgetEntity;
use Mautic\DashboardBundle\Model\DashboardModel;
use Mautic\UserBundle\Entity\User;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class WidgetTest extends TestCase
{
    private const USER_ID = 1;

    /**
     * @var DashboardModel|MockObject
     */
    private $dashboardModel;

    /**
     * @var UserHelper|MockObject
     */
    private $userHelper;

    /**
     * @var MockObject|Session
     */
    private $session;

    /**
     * @var User|MockObject
     */
    private $user;

    /**
     * @var Widget
     */
    private $widget;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dashboardModel = $this->createMock(DashboardModel::class);
        $this->userHelper     = $this->createMock(UserHelper::class);
        $this->session        = $this->createMock(Session::class);

        $this->user = $this->createMock(User::class);
        $this->user
            ->method('getId')
            ->willReturn(self::USER_ID);

        $this->widget = new Widget(
            $this->dashboardModel,
            $this->userHelper,
            $this->session
        );
    }

    public function testGetSuccess(): void
    {
        $widgetId = 2;
        $widget   = $this->createMock(WidgetEntity::class);
        $widget->expects(self::once())
            ->method('getId')
            ->willReturn($widgetId);
        $widget->expects(self::once())
            ->method('getCreatedBy')
            ->willReturn(self::USER_ID);

        $widget->setCreatedBy(self::USER_ID);
        $filter = [
            'dateFrom' => new Date(),
            'dateTo'   => new Date(),
        ];

        $this->dashboardModel->expects(self::once())
            ->method('getEntity')
            ->with($widgetId)
            ->willReturn($widget);

        $this->userHelper->expects(self::once())
            ->method('getUser')
            ->willReturn($this->user);

        $this->dashboardModel->expects(self::once())
            ->method('getDefaultFilter')
            ->willReturn($filter);

        $this->dashboardModel->expects(self::once())
            ->method('populateWidgetContent')
            ->with($widget, $filter);

        $this->widget->get($widgetId);
    }

    public function testGetNotFoundHttpException(): void
    {
        $widgetId = 2;
        $widget   = null;

        $this->dashboardModel->expects(self::once())
            ->method('getEntity')
            ->with($widgetId)
            ->willReturn($widget);

        $this->expectException(NotFoundHttpException::class);
        $this->widget->get($widgetId);
    }

    public function testGetNotFoundHttpExceptionEmptyEntity(): void
    {
        $widgetId = 2;
        $widget   = $this->createMock(WidgetEntity::class);
        $widget->expects(self::once())
            ->method('getId')
            ->willReturn(null);

        $widget->setCreatedBy(self::USER_ID);

        $this->dashboardModel->expects(self::once())
            ->method('getEntity')
            ->with($widgetId)
            ->willReturn($widget);

        $this->expectException(NotFoundHttpException::class);
        $this->widget->get($widgetId);
    }

    public function testGetAccessDeniedException(): void
    {
        $widgetId = 2;
        $widget   = $this->createMock(WidgetEntity::class);
        $widget->expects(self::once())
            ->method('getId')
            ->willReturn($widgetId);
        $widget->expects(self::once())
            ->method('getCreatedBy')
            ->willReturn(self::USER_ID + 1);

        $widget->setCreatedBy(self::USER_ID);

        $this->dashboardModel->expects(self::once())
            ->method('getEntity')
            ->with($widgetId)
            ->willReturn($widget);

        $this->userHelper->expects(self::once())
            ->method('getUser')
            ->willReturn($this->user);

        $this->expectException(AccessDeniedException::class);
        $this->widget->get($widgetId);
    }
}
