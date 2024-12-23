<?php

namespace Mautic\CoreBundle\Helper\DateTime;

use Symfony\Contracts\Translation\TranslatorInterface;

class DateTimeLocalization
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function localize(string $format): string
    {
        return str_replace($this->getDictionary(), array_keys($this->getDictionary()), $format);
    }

    /**
     * @return array<string,string>
     */
    private function getDictionary(): array
    {
        $months = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
        ];
        $days   = [
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday',
            'Sun',
            'Mon',
            'Tue',
            'Wed',
            'Thu',
            'Fri',
            'Sat',
        ];
        $values = array_merge($months, $days);
        $keys   = $values;
        array_walk($keys, function (&$key): void {
            $key = $this->translator->trans('mautic.core.date.'.strtolower($key));
        });

        return array_combine($keys, $values);
    }
}
