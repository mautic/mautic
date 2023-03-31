<?php

namespace Mautic\CoreBundle\Helper\DateTime;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

class DateTimeLocalization
{
    private static DateTimeLocalization $service;

    private CoreParametersHelper $coreParametersHelper;

    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator, CoreParametersHelper $coreParametersHelper)
    {
        $this->translator           = $translator;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function localize(string $date, ?string $contactLocale = null): string
    {
        $locale     = $contactLocale ?? $this->coreParametersHelper->get('locale');
        $dictionary = $this->getDictionary($this->getTranslationLocaleCore($locale));

        return str_replace($dictionary, array_keys($dictionary), $date);
    }

    /**
     * @return array<string,string>
     */
    private function getDictionary(?string $locale = null): array
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
        array_walk($keys, function (&$key) use ($locale) {
            $key = $this->translator->trans('mautic.core.date.'.strtolower($key), [], null, $locale);
        });

        return array_combine($keys, $values);
    }

    protected function getTranslationLocaleCore(string $locale): string
    {
        if (false !== strpos($locale, '_')) {
            $locale = substr($locale, 0, 2);
        }

        return $locale;
    }

    public function setService(): void
    {
        self::$service = $this;
    }

    public static function getService(): DateTimeLocalization
    {
        return self::$service;
    }
}
