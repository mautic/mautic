<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Form\Type;

use Mautic\CampaignBundle\Form\Type\EventType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EventTypeTest extends TestCase
{
    /**
     * @param string|array{date: string} $value
     */
    #[DataProvider('timeValueProvider')]
    public function testGetTimeValueParsesTimeOnlyStringsWithoutThrowing(string|array $value, string $expected): void
    {
        $type   = new EventType();
        $method = new \ReflectionMethod(EventType::class, 'getTimeValue');

        /** @var \DateTime $parsed */
        $parsed = $method->invoke($type, ['triggerHour' => $value], 'triggerHour');

        $this->assertInstanceOf(\DateTime::class, $parsed);
        $this->assertSame($expected, $parsed->format('H:i'));
    }

    public function testGetTimeValueReturnsNullForUnsupportedValue(): void
    {
        $type   = new EventType();
        $method = new \ReflectionMethod(EventType::class, 'getTimeValue');

        $parsed = $method->invoke($type, ['triggerHour' => 1], 'triggerHour');

        $this->assertNull($parsed);
    }

    /**
     * @return iterable<string, array{0: string|array{date: string}, 1: string}>
     */
    public static function timeValueProvider(): iterable
    {
        yield 'zero padded hour string' => ['04', '04:00'];
        yield 'zero padded hour string with whitespace' => [' 04 ', '04:00'];
        yield 'hour and minute string' => ['04:00', '04:00'];
        yield 'hour and minute string with whitespace' => [' 04:00 ', '04:00'];
        yield 'fallback datetime string with whitespace' => [' 2024-01-02 03:04:00 ', '03:04'];
        yield 'array date value' => [['date' => '08:00'], '08:00'];
    }
}
