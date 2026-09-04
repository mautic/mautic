<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\DateTimeHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class DateHelper
{
    /**
     * @var array<string, string>
     */
    private array $formats;

    private DateTimeHelper $helper;

    public function __construct(
        string $dateFullFormat,
        string $dateShortFormat,
        string $dateOnlyFormat,
        string $timeOnlyFormat,
        private TranslatorInterface $translator,
        private CoreParametersHelper $coreParametersHelper,
        ?DateTimeHelper $helper = null,
    ) {
        $this->formats = [
            'datetime' => $dateFullFormat,
            'short'    => $dateShortFormat,
            'date'     => $dateOnlyFormat,
            'time'     => $timeOnlyFormat,
        ];

        $this->helper = $helper ?? new DateTimeHelper('', 'Y-m-d H:i:s', 'local');
    }

    private function format(string $type, mixed $datetime, string $timezone, ?string $fromFormat): string
    {
        if (empty($datetime)) {
            return '';
        }
        $this->helper->setDateTime($datetime, $fromFormat, $timezone);

        return $this->helper->toLocalString(
            $this->formats[$type]
        );
    }

    /**
     * Returns full date. eg. October 8, 2014 21:19.
     *
     * @param \DateTimeInterface|string $datetime
     */
    public function toFull(mixed $datetime, string $timezone = 'local', ?string $fromFormat = 'Y-m-d H:i:s'): string
    {
        return $this->format('datetime', $datetime, $timezone, $fromFormat);
    }

    /**
     * Returns date and time concat eg 2014-08-02 5:00am.
     *
     * @param \DateTimeInterface|string $datetime
     */
    public function toFullConcat(mixed $datetime, string $timezone = 'local', ?string $fromFormat = 'Y-m-d H:i:s'): string
    {
        $this->helper->setDateTime($datetime, $fromFormat, $timezone);

        return $this->helper->toLocalString(
            $this->formats['date'].' '.$this->formats['time']
        );
    }

    /**
     * Returns short date format eg Sun, Oct 8.
     *
     * @param \DateTimeInterface|string $datetime
     */
    public function toShort(mixed $datetime, string $timezone = 'local', ?string $fromFormat = 'Y-m-d H:i:s'): string
    {
        return $this->format('short', $datetime, $timezone, $fromFormat);
    }

    /**
     * Returns date only e.g. 2014-08-09.
     *
     * @param \DateTimeInterface|string $datetime
     */
    public function toDate(mixed $datetime, string $timezone = 'local', ?string $fromFormat = 'Y-m-d H:i:s'): string
    {
        return $this->format('date', $datetime, $timezone, $fromFormat);
    }

    /**
     * Returns time only e.g. 21:19.
     *
     * @param \DateTimeInterface|string $datetime
     */
    public function toTime(mixed $datetime, string $timezone = 'local', ?string $fromFormat = 'Y-m-d H:i:s'): string
    {
        return $this->format('time', $datetime, $timezone, $fromFormat);
    }

    /**
     * Returns date/time like Today, 10:00 AM.
     *
     * @param string|int<min, -1>|int<1, max>|\DateTimeInterface $datetime
     * @param bool                                               $forceDateForNonText If true, return as full date/time rather than "29 days ago"
     */
    public function toText(mixed $datetime, string $timezone = 'local', ?string $fromFormat = 'Y-m-d H:i:s', bool $forceDateForNonText = false): string
    {
        if (empty($datetime)) {
            return '';
        }

        $this->helper->setDateTime($datetime, $fromFormat, $timezone);

        $textDate = $this->helper->getTextDate();
        $dt       = $this->helper->getLocalDateTime();

        if ($textDate) {
            return $this->translator->trans('mautic.core.date.'.$textDate, ['%time%' => $dt->format($this->coreParametersHelper->get('date_format_timeonly'))]);
        }
        $interval = $this->helper->getDiff('now', null, true);

        if ($interval->invert && !$forceDateForNonText) {
            // In the past
            return $this->translator->trans('mautic.core.date.ago', ['%days%' => $interval->days]);
        }

        // In the future
        return $this->toFullConcat($datetime, $timezone, $fromFormat);
    }

    /**
     * Format DateInterval into humanly readable format.
     * Example: 55 minutes 49 seconds.
     * It doesn't return zero values like 0 years.
     */
    public function formatRange(\DateInterval $range): string
    {
        $formated  = [];
        $timeUnits = ['y' => 'year', 'm' => 'month', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second'];

        foreach ($timeUnits as $key => $unit) {
            if ($range->{$key}) {
                $formated[] = $this->translator->trans(
                    'mautic.core.date.'.$unit,
                    ['%count%' => $range->{$key}]
                );
            }
        }

        if ([] === $formated) {
            return $this->translator->trans('mautic.core.date.less.than.second');
        }

        return implode(' ', $formated);
    }

    public function getFullFormat(): string
    {
        return $this->formats['datetime'];
    }

    public function getDateFormat(): string
    {
        return $this->formats['date'];
    }

    public function getTimeFormat(): string
    {
        return $this->formats['time'];
    }

    public function getShortFormat(): string
    {
        return $this->formats['short'];
    }

    public function getName(): string
    {
        return 'date';
    }

    /**
     * Returns a humanized date string like "X hours ago" or "in X hours".
     *
     * @param \DateTimeInterface|string $datetime
     */
    public function toHumanized(mixed $datetime, string $timezone = 'local', ?string $fromFormat = 'Y-m-d H:i:s'): string
    {
        if (empty($datetime)) {
            return '';
        }

        $this->helper->setDateTime($datetime, $fromFormat, $timezone);
        $date = $this->helper->getDateTime();

        $nowTimezone = ('local' === $timezone) ? date_default_timezone_get() : $timezone;
        $now         = new \DateTime('now', new \DateTimeZone($nowTimezone));

        $diff     = $now->diff($date);
        $isFuture = $date > $now;

        return $this->getHumanizedTimeString($diff, $isFuture);
    }

    private function getHumanizedTimeString(\DateInterval $diff, bool $isFuture): string
    {
        if ($diff->y > 0) {
            return $isFuture
                ? $this->translator->trans('mautic.core.date.years.in', ['%count%' => $diff->y])
                : $this->translator->trans('mautic.core.date.years.ago', ['%count%' => $diff->y]);
        }
        if ($diff->m > 0) {
            return $isFuture
                ? $this->translator->trans('mautic.core.date.months.in', ['%count%' => $diff->m])
                : $this->translator->trans('mautic.core.date.months.ago', ['%count%' => $diff->m]);
        }
        if ($diff->d > 0) {
            return $isFuture
                ? $this->translator->trans('mautic.core.date.days.in', ['%count%' => $diff->d])
                : $this->translator->trans('mautic.core.date.days.ago', ['%count%' => $diff->d]);
        }
        if ($diff->h > 0) {
            return $isFuture
                ? $this->translator->trans('mautic.core.date.hours.in', ['%count%' => $diff->h])
                : $this->translator->trans('mautic.core.date.hours.ago', ['%count%' => $diff->h]);
        }
        if ($diff->i > 0) {
            return $isFuture
                ? $this->translator->trans('mautic.core.date.minutes.in', ['%count%' => $diff->i])
                : $this->translator->trans('mautic.core.date.minutes.ago', ['%count%' => $diff->i]);
        }

        return $this->translator->trans('mautic.core.date.just.now');
    }

    /**
     * Returns short text date like "Today", "Yesterday", or formatted date.
     *
     * @param \DateTimeInterface|string $datetime
     */
    public function toTextShort(mixed $datetime, string $timezone = 'local', ?string $fromFormat = 'Y-m-d H:i:s'): string
    {
        if (empty($datetime)) {
            return '';
        }

        $this->helper->setDateTime($datetime, $fromFormat, $timezone);
        $textDate = $this->helper->getTextDate();

        if ($textDate) {
            $translated = $this->translator->trans('mautic.core.date.'.$textDate, ['%time%' => '']);

            return trim(str_replace(',', '', $translated));
        }

        // For other dates, return a formatted date
        return $this->format('date', $datetime, $timezone, $fromFormat);
    }
}
