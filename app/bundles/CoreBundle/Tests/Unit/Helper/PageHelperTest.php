<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PageHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PageHelperTest extends \PHPUnit\Framework\TestCase
{
    private MockObject&SessionInterface $session;

    private PageHelper $pageHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session              = $this->createMock(SessionInterface::class);
        $requestStack               = $this->createMock(RequestStack::class);
        $coreParametersHelper       = $this->createMock(CoreParametersHelper::class);
        $this->pageHelper           = new PageHelper($requestStack, $coreParametersHelper, 'mautic.test', 0);

        $requestStack->method('getSession')->willReturn($this->session);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('PageProvider')]
    public function testCountPage(int $count, int $limit, int $page): void
    {
        $this->session->expects($this->once())
            ->method('get')
            ->with('mautic.test.limit')
            ->willReturn($limit);

        $this->assertSame($page, $this->pageHelper->countPage($count));
    }

    /** @return array<int, array{0: int, 1: int, 2: int}> */
    public static function pageProvider(): array
    {
        return [
            [0, 10, 1],
            [1, 10, 1],
            [5, 10, 1],
            [10, 10, 1],
            [11, 10, 2],
            [20, 10, 2],
            [21, 10, 3],
            [15, 15, 1],
            [16, 15, 2],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('startProvider')]
    public function testCountStart(int $page, int $limit, int $start): void
    {
        $this->session->expects($this->once())
            ->method('get')
            ->with('mautic.test.limit')
            ->willReturn($limit);

        $this->assertSame($start, $this->pageHelper->countPage($page));
    }

    /** @return array<int, array{0: int, 1: int, 2: int}> */
    public static function startProvider(): array
    {
        return [
            [0, 10, 1],
            [1, 10, 1],
            [10, 10, 1],
            [11, 10, 2],
        ];
    }
}
