<?php

namespace Mautic\CoreBundle\Helper\DateTime;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

class DateTimeToken
{
    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
        private DateTimeLocalization $dateTimeLocalization
    ) {
    }

    /**
     * @return array<string>
     */
    public function getTokens(string $content, string $contactTimezone = null): array
    {
        $tokens = [];
        preg_match_all('/{today(.*?)}/', $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $key => $modifier) {
                $token = $matches[0][$key];

                if (isset($tokens[$token])) {
                    continue;
                }

                $tokens[$token] = $this->getToday($modifier, $contactTimezone);
            }
        }

        return $tokens;
    }

    private function getToday(string $modifier, ?string $contactTimezone): string
    {
        $defaultDateFormat     = $this->coreParametersHelper->get('date_format_dateonly');
        $defaultTimeFormat     = $this->coreParametersHelper->get('date_format_timeonly');
        $defaultDatetimeFormat = sprintf('%s %s', $defaultDateFormat, $defaultTimeFormat);
        $contactTimezone       = $contactTimezone ?: $this->coreParametersHelper->get('default_timezone', 'UTC');
        $dateTime              = new \DateTime('now', new \DateTimeZone($contactTimezone));

        $parseModifier = explode('|', ltrim($modifier, '|'));
        $modifier      = $parseModifier[0] ?? '';
        $relativeDate  = $parseModifier[1] ?? '';

        $format = match ($modifier) {
            'datetime' => $defaultDatetimeFormat,
            'date'     => $defaultDateFormat,
            'time'     => $defaultTimeFormat,
            default    => $modifier ?: $defaultDatetimeFormat,
        };

        if ($relativeDate) {
            $dateTime->modify($relativeDate);
        }

        return $this->dateTimeLocalization->localize($dateTime->format($format));
    }
}
