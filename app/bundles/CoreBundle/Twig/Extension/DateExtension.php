<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\DateHelper;

class DateExtension
{
    public function __construct(
        protected DateHelper $dateHelper,
    ) {
    }

    /**
     * Returns date/time like Today, 10:00 AM.
     *
     * @param mixed $datetime
     * @param bool  $forceDateForNonText If true, return as full date/time rather than "29 days ago"
     */
    #[\Twig\Attribute\AsTwigFunction('dateToText', isSafe: ['all'])]
    public function toText($datetime, string $timezone = 'local', string $fromFormat = 'Y-m-d H:i:s', bool $forceDateForNonText = false): string
    {
        return $this->dateHelper->toText($datetime, $timezone, $fromFormat, $forceDateForNonText);
    }

    /**
     * Returns a humanized date string like "X hours ago".
     *
     * @param \DateTime|string $datetime
     */
    #[\Twig\Attribute\AsTwigFunction('dateToHumanized', isSafe: ['all'])]
    public function toHumanized($datetime, string $timezone = 'local', string $fromFormat = 'Y-m-d H:i:s'): string
    {
        return $this->dateHelper->toHumanized($datetime, $timezone, $fromFormat);
    }

    /**
     * Returns full date. eg. October 8, 2014 21:19.
     *
     * @param \DateTime|string $datetime
     */
    #[\Twig\Attribute\AsTwigFunction('dateToFull', isSafe: ['all'])]
    public function toFull($datetime, string $timezone = 'local', string $fromFormat = 'Y-m-d H:i:s'): string
    {
        return $this->dateHelper->toFull($datetime, $timezone, $fromFormat);
    }

    /**
     * Returns date and time concat eg 2014-08-02 5:00am.
     *
     * @param \DateTime|string $datetime
     * @param string           $timezone
     * @param string           $fromFormat
     *
     * @return string
     */
    #[\Twig\Attribute\AsTwigFunction('dateToFullConcat', isSafe: ['all'])]
    public function toFullConcat($datetime, $timezone = 'local', $fromFormat = 'Y-m-d H:i:s')
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
    #[\Twig\Attribute\AsTwigFunction('dateToDate', isSafe: ['all'])]
    public function toDate($datetime, string $timezone = 'local', string $fromFormat = 'Y-m-d H:i:s')
    {
        return $this->dateHelper->toDate($datetime, $timezone, $fromFormat);
    }

    /**
     * Returns time only e.g. 21:19.
     *
     * @param \DateTime|string $datetime
     */
    #[\Twig\Attribute\AsTwigFunction('dateToTime', isSafe: ['all'])]
    public function toTime($datetime, string $timezone = 'local', string $fromFormat = 'Y-m-d H:i:s'): string
    {
        return $this->dateHelper->toTime($datetime, $timezone, $fromFormat);
    }

    /**
     * Returns short date format eg Sun, Oct 8.
     *
     * @param \DateTime|string $datetime
     */
    #[\Twig\Attribute\AsTwigFunction('dateToShort', isSafe: ['all'])]
    public function toShort($datetime, string $timezone = 'local', string $fromFormat = 'Y-m-d H:i:s'): string
    {
        return $this->dateHelper->toShort($datetime, $timezone, $fromFormat);
    }

    /**
     * @see DateHelper::formatRange
     */
    #[\Twig\Attribute\AsTwigFunction('dateFormatRange', isSafe: ['all'])]
    public function formatRange(\DateInterval $range): string
    {
        return $this->dateHelper->formatRange($range);
    }
}
