<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Unit\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\PageBundle\Helper\AggregateAnalyticsHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AggregateAnalyticsHelperTest extends TestCase
{
    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function settingProvider(): iterable
    {
        yield 'on' => [true, true];
        yield 'off' => [false, false];
        yield 'off as saved by the configuration form' => ['0', false];
    }

    #[DataProvider('settingProvider')]
    public function testFollowsTheSetting(mixed $value, bool $expected): void
    {
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')->with('aggregate_analytics', true)->willReturn($value);

        $this->assertSame($expected, (new AggregateAnalyticsHelper($coreParametersHelper))->isEnabled());
    }

    public function testIsOnWhenNotConfigured(): void
    {
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')->willReturnArgument(1);

        $this->assertTrue((new AggregateAnalyticsHelper($coreParametersHelper))->isEnabled());
    }
}
