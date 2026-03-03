<?php

declare(strict_types=1);

namespace Mautic\DashboardBundle\Tests\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\CacheBundle\Cache\CacheProviderTagAwareInterface;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\Filesystem;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\DashboardBundle\Factory\WidgetDetailEventFactory;
use Mautic\DashboardBundle\Model\DashboardModel;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class DashboardModelTest extends TestCase
{
    private MockObject&CoreParametersHelper $coreParametersHelper;
    private MockObject&Session $session;
    private DashboardModel $model;

    protected function setUp(): void
    {
        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->session              = $this->createMock(Session::class);
        $requestStack               = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')
            ->willReturn($this->session);

        $this->model = new DashboardModel(
            $this->coreParametersHelper,
            $this->createMock(PathsHelper::class),
            $this->createMock(WidgetDetailEventFactory::class),
            $this->createMock(Filesystem::class),
            $requestStack,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(CorePermissions::class),
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(Translator::class),
            $this->createMock(UserHelper::class),
            $this->createMock(LoggerInterface::class),
            $this->createMock(CacheProviderTagAwareInterface::class),
        );
    }

    public function testGetDefaultFilterFromSession(): void
    {
        $dateFromStr = '-1 month';
        $dateFrom    = new \DateTime($dateFromStr);
        $dateTo      = new \DateTime('23:59:59'); // till end of the 'to' date selected

        $this->coreParametersHelper->expects(self::once())
            ->method('get')
            ->with('default_daterange_filter', $dateFromStr)
            ->willReturn($dateFromStr);

        $this->session->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $dateFrom->format(\DateTimeInterface::ATOM),
                $dateTo->format(\DateTimeInterface::ATOM)
            );

        $filter = $this->model->getDefaultFilter();

        Assert::assertSame(
            $dateFrom->format(\DateTimeInterface::ATOM),
            $filter['dateFrom']->format(\DateTimeInterface::ATOM)
        );

        Assert::assertSame(
            $dateTo->format(\DateTimeInterface::ATOM),
            $filter['dateTo']->format(\DateTimeInterface::ATOM)
        );
    }
}
