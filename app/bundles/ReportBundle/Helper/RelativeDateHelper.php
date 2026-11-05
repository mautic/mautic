<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Helper;

use Mautic\CoreBundle\Helper\DateTimeHelper;

final class RelativeDateHelper
{
    public static function isRelative(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $value = trim($value);
        if (self::isAnniversary($value)) {
            $value = trim(str_ireplace(['anniversary', 'birthday'], '', $value));
            if ('' === $value) {
                return true;
            }
        } elseif (1 !== preg_match(
            '/^(?:today|tomorrow|yesterday|(?:this|last|next)\s+(?:week|month|year)|[+-].+|.+\sago|(?:first|last)\s+day\s+of\s+.+)$/iD',
            $value
        )) {
            return false;
        }

        try {
            new \DateTimeImmutable($value);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    public static function isAnniversary(string $value): bool
    {
        return str_contains(strtolower($value), 'anniversary') || str_contains(strtolower($value), 'birthday');
    }

    public static function isCalendarPeriod(string $value): bool
    {
        return 1 === preg_match('/^(?:today|tomorrow|yesterday|(?:this|last|next)\s+(?:week|month|year))$/iD', trim($value));
    }

    public static function resolveInstant(string $value, bool $dateOnly): string
    {
        $date = new DateTimeHelper($value, null, 'local');

        return $dateOnly ? $date->toLocalString(DateTimeHelper::FORMAT_DB_DATE_ONLY) : $date->toUtcString();
    }

    public static function resolveAnniversary(string $value): string
    {
        $relativeValue = trim(str_ireplace(['anniversary', 'birthday'], '', $value));
        $date          = new DateTimeHelper($relativeValue, null, 'local');

        return $date->toLocalString('m-d');
    }

    /**
     * @return array{start: string, end: string}
     */
    public static function resolveRange(string $value, bool $dateOnly): array
    {
        $value  = trim($value);
        $period = 'day';
        if (1 === preg_match('/^(?:this|last|next)\s+(week|month|year)$/iD', $value, $matches)) {
            $period = strtolower($matches[1]);
            if ('month' === $period) {
                $value = 'first day of '.$value;
            } elseif ('year' === $period) {
                $value = 'first day of January '.$value;
            }
        }

        $date  = new DateTimeHelper($value, null, 'local');
        $start = $date->getLocalDateTime()->setTime(0, 0);

        $end    = (clone $start)->modify('+1 '.$period)->modify('-1 second');
        $format = $dateOnly ? DateTimeHelper::FORMAT_DB_DATE_ONLY : DateTimeHelper::FORMAT_DB;

        if (!$dateOnly) {
            $utc = new \DateTimeZone('UTC');
            $start->setTimezone($utc);
            $end->setTimezone($utc);
        }

        return ['start' => $start->format($format), 'end' => $end->format($format)];
    }
}
