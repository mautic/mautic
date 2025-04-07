<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Decorator\Date;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Segment\Decorator\Date\TimezoneResolver;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class TimezoneResolverTest extends TestCase
{
    /**
     * @dataProvider dataTimezones
     */
    public function testTimezones(?string $configuredTimezone, string $expectedTimezone): void
    {
        $coreParametersHelper = new class($configuredTimezone) extends CoreParametersHelper {
            public function __construct(private ?string $configuredTimezone)
            {
            }

            public function get($name, $default = null)
            {
                Assert::assertSame('default_timezone', $name);

                return $this->configuredTimezone;
            }
        };

        $timezoneResolver = new TimezoneResolver($coreParametersHelper);
        Assert::assertSame(
            $expectedTimezone,
            $timezoneResolver->getDefaultDate(false)->getDateTime()->getTimezone()->getName()
        );
    }

    /**
     * @return iterable<string, array<?string>>
     */
    public function dataTimezones(): iterable
    {
        yield 'Default timezone' => [null, 'UTC'];
        yield 'UTC timezone'     => ['UTC', 'UTC'];
        yield 'Prague timezone'  => ['Europe/Prague', 'Europe/Prague'];
    }
}
