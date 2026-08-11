<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\DateHelper;
use Twig\Attribute\AsTwigFunction;

final readonly class DateExtension
{
    public function __construct(
        private DateHelper $dateHelper,
    ) {
    }

    /**
     * Returns date/time like Today, 10:00 AM.
     *
     * @param mixed $datetime
     * @param bool  $forceDateForNonText If true, return as full date/time rather than "29 days ago"
     */
    #[AsTwigFunction(name: 'dateToText', isSafe: ['all'])]
    public function toText($datetime, string $timezone = 'local', string $fromFormat = 'Y-m-d H:i:s', bool $forceDateForNonText = false): string
    {
        return $this->dateHelper->toText($datetime, $timezone, $fromFormat, $forceDateForNonText);
    }

    /**
     * Returns a humanized date string like "X hours ago".
     *
     * @param \DateTime|string $datetime
     */
    #[AsTwigFunction(name: 'dateToHumanized', isSafe: ['all'])]
    public function toHumanized($datetime, string $timezone = 'local', string $fromFormat = 'Y-m-d H:i:s'): string
    {
        return $this->dateHelper->toHumanized($datetime, $timezone, $fromFormat);
    }

    /**
     * Returns full date. eg. October 8, 2014 21:19.
     *
     * @param \DateTime|string $datetime
     */
    #[AsTwigFunction(name: 'dateToFull', isSafe: ['all'])]
    public function toFull($datetime, string $timezone = 'local', string $fromFormat = 'Y-m-d H:i:s'): string
    {
        return $this->dateHelper->toFull($datetime, $timezone, $fromFormat);
    }

    /**
     * Returns date and time concat eg 2014-08-02 5:00am.
     *
     * @param \DateTime|string $datetime
     *
     * @return string
     */
    #[AsTwigFunction(name: 'dateToFullConcat', isSafe: ['all'])]
    public function toFullConcat($datetime, string $timezone = 'local', ?string $fromFormat = 'Y-m-d H:i:s')
    {
        return $this->dateHelper->toFullConcat($datetime, $timezone, $fromFormat);
    }

    /**
     * Returns date only e.g. 2014-08-09.
     *
     * @param \DateTime|string $datetime
     *
     * @return string
     */
    #[AsTwigFunction(name: 'dateToDate', isSafe: ['all'])]
    public function toDate($datetime, string $timezone = 'local', string $fromFormat = 'Y-m-d H:i:s')
    {
        return $this->dateHelper->toDate($datetime, $timezone, $fromFormat);
    }

    /**
     * Returns time only e.g. 21:19.
     *
     * @param \DateTime|string $datetime
     */
    #[AsTwigFunction(name: 'dateToTime', isSafe: ['all'])]
    public function toTime($datetime, string $timezone = 'local', string $fromFormat = 'Y-m-d H:i:s'): string
    {
        return $this->dateHelper->toTime($datetime, $timezone, $fromFormat);
    }

    /**
     * Returns short date format eg Sun, Oct 8.
     *
     * @param \DateTime|string $datetime
     */
    #[AsTwigFunction(name: 'dateToShort', isSafe: ['all'])]
    public function toShort($datetime, string $timezone = 'local', string $fromFormat = 'Y-m-d H:i:s'): string
    {
        return $this->dateHelper->toShort($datetime, $timezone, $fromFormat);
    }

    /**
     * Returns short text date like "Today", "Yesterday", or formatted date.
     *
     * @param \DateTime|string $datetime
     */
    #[AsTwigFunction(name: 'dateToTextShort', isSafe: ['all'])]
    public function toTextShort($datetime, string $timezone = 'local', string $fromFormat = 'Y-m-d H:i:s'): string
    {
        return $this->dateHelper->toTextShort($datetime, $timezone, $fromFormat);
    }

    /**
     * @see DateHelper::formatRange
     */
    #[AsTwigFunction(name: 'dateFormatRange', isSafe: ['all'])]
    public function formatRange(\DateInterval $range): string
    {
        return $this->dateHelper->formatRange($range);
    }
}
