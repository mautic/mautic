<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PageHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class PageHelperTest extends \PHPUnit\Framework\TestCase
{
    private MockObject&SessionInterface $session;

    private PageHelper $pageHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session              = $this->createMock(SessionInterface::class);
        $requestStack               = $this->createMock(RequestStack::class);
        $this->pageHelper           = new PageHelper($requestStack, $this->createStub(CoreParametersHelper::class), 'mautic.test', 0);

        $requestStack->method('getSession')->willReturn($this->session);
    }

    #[DataProvider('PageProvider')]
    public function testCountPage(int $count, int $limit, int $page): void
    {
        $this->session->expects($this->once())
            ->method('get')
            ->with('mautic.test.limit')
            ->willReturn($limit);

        $this->assertSame($page, $this->pageHelper->countPage($count));
    }

    /**
     * @return \Iterator<int, array{int, int, int}>
     */
    public static function pageProvider(): \Iterator
    {
        yield [0, 10, 1];
        yield [1, 10, 1];
        yield [5, 10, 1];
        yield [10, 10, 1];
        yield [11, 10, 2];
        yield [20, 10, 2];
        yield [21, 10, 3];
        yield [15, 15, 1];
        yield [16, 15, 2];
    }

    #[DataProvider('startProvider')]
    public function testCountStart(int $page, int $limit, int $start): void
    {
        $this->session->expects($this->once())
            ->method('get')
            ->with('mautic.test.limit')
            ->willReturn($limit);

        $this->assertSame($start, $this->pageHelper->countPage($page));
    }

    /**
     * @return \Iterator<int, array{int, int, int}>
     */
    public static function startProvider(): \Iterator
    {
        yield [0, 10, 1];
        yield [1, 10, 1];
        yield [10, 10, 1];
        yield [11, 10, 2];
    }
}
