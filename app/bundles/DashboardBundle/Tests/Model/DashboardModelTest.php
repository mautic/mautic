<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Tests\Model;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\DashboardBundle\Model\DashboardModel;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Session\Session;

class DashboardModelTest extends TestCase
{
    /**
     * @var CoreParametersHelper|MockObject
     */
    private $coreParametersHelper;

    /**
     * @var PathsHelper|MockObject
     */
    private $pathsHelper;

    /**
     * @var MockObject|Filesystem
     */
    private $filesystem;

    /**
     * @var MockObject|Session
     */
    private $session;

    /**
     * @var DashboardModel
     */
    private $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $this->pathsHelper          = $this->createMock(PathsHelper::class);
        $this->filesystem           = $this->createMock(Filesystem::class);

        $this->model = new DashboardModel(
            $this->coreParametersHelper,
            $this->pathsHelper,
            $this->filesystem
        );

        $this->session = $this->createMock(Session::class);

        $this->model->setSession($this->session);
    }

    public function testGetDefaultFilterFromSession(): void
    {
        $dateFrom = '-1 month';

        $this->coreParametersHelper->expects(self::once())
            ->method('get')
            ->with('default_daterange_filter', $dateFrom)
            ->willReturn($dateFrom);

        $dateFrom = new \DateTime($dateFrom);
        $this->session->expects(self::at(0))
            ->method('get')
            ->with('mautic.daterange.form.from')
            ->willReturn($dateFrom->format(\DateTimeInterface::ATOM));

        $dateTo = new \DateTime();
        $this->session->expects(self::at(1))
            ->method('get')
            ->with('mautic.daterange.form.to')
            ->willReturn($dateTo->format(\DateTimeInterface::ATOM));

        $filter = $this->model->getDefaultFilter();

        self::assertSame(
            $dateFrom->format(\DateTimeInterface::ATOM),
            $filter['dateFrom']->format(\DateTimeInterface::ATOM)
        );

        self::assertSame(
            $dateTo->format(\DateTimeInterface::ATOM),
            $filter['dateTo']->format(\DateTimeInterface::ATOM)
        );
    }
}
