<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper\DateTime;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTime\DateTimeLocalization;
use Mautic\CoreBundle\Helper\DateTime\DateTimeToken;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DateTokenHelperTest extends \PHPUnit\Framework\TestCase
{
    public const DATE_FORMAT      = 'F j, Y';

    public const TIME_FORMAT      = 'g:i a';

    public const DATE_TIME_FORMAT = self::DATE_FORMAT.' '.self::TIME_FORMAT;

    public const TIMEZONE        = 'Europe/Paris';

    public const TIMEZONE_CUSTOM = 'America/Chicago';

    public function testGetTokens(): void
    {
        $coreParametersHelper = new class($this->createMock(ContainerInterface::class)) extends CoreParametersHelper {
            public function get($name, $default = null)
            {
                switch ($name) {
                    case 'default_timezone':
                        return DateTokenHelperTest::TIMEZONE;
                    case 'date_format_dateonly':
                        return DateTokenHelperTest::DATE_FORMAT;
                    case 'date_format_timeonly':
                        return DateTokenHelperTest::TIME_FORMAT;
                }
            }
        };

        $dateTimeLocalization = new class($this->createMock(TranslatorInterface::class)) extends DateTimeLocalization {
            public function localize(string $format): string
            {
                return $format;
            }
        };

        $dateTokenHelper = new DateTimeToken($coreParametersHelper, $dateTimeLocalization);
        foreach ($this->getContents() as $contents) {
            $content         = $contents[0];
            $expected        = $contents[1];
            $contactTimezone = $contents[2] ?? null;

            $tokens          = $dateTokenHelper->getTokens($content, $contactTimezone);
            $this->assertSame($expected, $tokens);
        }
    }

    /**
     * @return array<int, array<int, array<string, string>|string>>
     */
    public function getContents(): array
    {
        $now      = new \DateTime('now', new \DateTimeZone(self::TIMEZONE));
        $contents =  [
            [
                '<html lang="en"><head><title></title></head><body>{today}</body></html>',
                [
                    '{today}' => $now->format(self::DATE_TIME_FORMAT),
                ],
            ], [
                '<html lang="en"><head><title></title></head><body>{today|datetime}</body></html>',
                [
                    '{today|datetime}' => $now->format(self::DATE_TIME_FORMAT),
                ],
            ],
            [
                '<html lang="en"><head><title></title></head><body>{today|date}</body></html>',
                [
                    '{today|date}' => $now->format(self::DATE_FORMAT),
                ],
            ], [
                '<html lang="en"><head><title></title></head><body>{today|time}</body></html>',
                [
                    '{today|time}' => $now->format(self::TIME_FORMAT),
                ],
            ], [
                '<html lang="en"><head><title></title></head><body>{today|date} {today|time}</body></html>',
                [
                    '{today|date}' => $now->format(self::DATE_FORMAT),
                    '{today|time}' => $now->format(self::TIME_FORMAT),
                ],
            ], [
                '<html lang="en"><head><title></title></head><body>{today|'.self::DATE_TIME_FORMAT.'}</body></html>',
                [
                    '{today|'.self::DATE_TIME_FORMAT.'}' => $now->format(self::DATE_TIME_FORMAT),
                ],
            ], [
                '<html lang="en"><head><title></title></head><body>{today|'.self::DATE_TIME_FORMAT.'|+ 1 month}</body></html>',
                [
                    '{today|'.self::DATE_TIME_FORMAT.'|+ 1 month}' => (clone $now)->modify('+1 month')->format(self::DATE_TIME_FORMAT),
                ],
            ], [
                '<html lang="en"><head><title></title></head><body>{today|'.self::DATE_FORMAT.'|+1 day}</body></html>',
                [
                    '{today|'.self::DATE_FORMAT.'|+1 day}' => (clone $now)->modify('+1 day')->format(self::DATE_FORMAT),
                ],
            ], [
                '<html lang="en"><head><title></title></head><body></body></html>',
                [],
            ],
        ];

        $now        = (clone $now)->setTimezone(new \DateTimeZone(self::TIMEZONE_CUSTOM));
        $contents[] = [
            '<html lang="en"><head><title></title></head><body>{today|'.self::DATE_TIME_FORMAT.'|+1 day}</body></html>',
            [
                '{today|'.self::DATE_TIME_FORMAT.'|+1 day}' => $now->modify('+1 day')->format(self::DATE_TIME_FORMAT),
            ],
            self::TIMEZONE_CUSTOM,
        ];

        return $contents;
    }
}
