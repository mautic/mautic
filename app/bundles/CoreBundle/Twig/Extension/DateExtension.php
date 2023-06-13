<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DateExtension extends AbstractExtension
{
    public function __construct(protected DateHelper $dateHelper)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('dateToText', [$this, 'toText'], ['is_safe' => ['all']]),
            new TwigFunction('dateToFull', [$this, 'toFull'], ['is_safe' => ['all']]),
            new TwigFunction('dateToFullConcat', [$this, 'toFullConcat'], ['is_safe' => ['all']]),
            new TwigFunction('dateToDate', [$this, 'toDate'], ['is_safe' => ['all']]),
            new TwigFunction('dateToTime', [$this, 'toTime'], ['is_safe' => ['all']]),
            new TwigFunction('dateToShort', [$this, 'toShort'], ['is_safe' => ['all']]),
            new TwigFunction('dateFormatRange', [$this, 'formatRange'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * Returns date/time like Today, 10:00 AM.
     *
     * @param bool $forceDateForNonText If true, return as full date/time rather than "29 days ago"
     */
    public function toText(mixed $datetime, string $timezone = 'local', string $fromFormat = 'Y-m-d H:i:s', bool $forceDateForNonText = false): string
    {
        return $this->dateHelper->toText($datetime, $timezone, $fromFormat, $forceDateForNonText);
    }

    /**
     * Returns full date. eg. October 8, 2014 21:19.
     */
    public function toFull(\DateTime|string|null $datetime, string $timezone = 'local', string $fromFormat = 'Y-m-d H:i:s'): string
    {
        return $this->dateHelper->toFull($datetime, $timezone, $fromFormat);
    }

    /**
     * Returns date and time concat eg 2014-08-02 5:00am.
     *
     * @param string $timezone
     * @param string $fromFormat
     *
     * @return string
     */
    public function toFullConcat(\DateTime|string|\DateTimeImmutable $datetime, $timezone = 'local', $fromFormat = 'Y-m-d H:i:s')
    {
        return $this->dateHelper->toFullConcat($datetime, $timezone, $fromFormat);
    }

    /**
     * Returns date only e.g. 2014-08-09.
     *
     * @return string
     */
    public function toDate(\DateTime|string|\DateTimeImmutable $datetime, string $timezone = 'local', string $fromFormat = 'Y-m-d H:i:s')
    {
        return $this->dateHelper->toDate($datetime, $timezone, $fromFormat);
    }

    /**
     * Returns time only e.g. 21:19.
     */
    public function toTime(\DateTime|string $datetime, string $timezone = 'local', string $fromFormat = 'Y-m-d H:i:s'): string
    {
        return $this->dateHelper->toTime($datetime, $timezone, $fromFormat);
    }

    /**
     * Returns short date format eg Sun, Oct 8.
     */
    public function toShort(\DateTime|string $datetime, string $timezone = 'local', string $fromFormat = 'Y-m-d H:i:s'): string
    {
        return $this->dateHelper->toShort($datetime, $timezone, $fromFormat);
    }

    /**
     * @see DateHelper::formatRange
     */
    public function formatRange(\DateInterval $range): string
    {
        return $this->dateHelper->formatRange($range);
    }
}
