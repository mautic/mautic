<?php

namespace Mautic\CoreBundle\Helper;

use Symfony\Contracts\Translation\TranslatorInterface;

class DateTokenHelper
{
    private CoreParametersHelper $coreParametersHelper;

    private TranslatorInterface $translator;

    public function __construct(CoreParametersHelper $coreParametersHelper, TranslatorInterface $translator)
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->translator           = $translator;
    }

    /**
     * @return array<string>
     */
    public function getTokens(string $content, string $contactTimezone = null): array
    {
        $tokens       = [];
        $matchesArray = [];

        preg_match_all(
            '/{today(.*?)}/',
            $content,
            $matchesDefault
        );
        $matchesArray[] = $matchesDefault;
        preg_match_all(
            '/{'.$this->translator->trans('mautic.lead.list.today').'(.*?)}/',
            $content,
            $matchesTranslation
        );
        $matchesArray[] = $matchesTranslation;
        foreach ($matchesArray as $matches) {
            if (!empty($matches[1])) {
                foreach ($matches[1] as $key => $modifier) {
                    $token = $matches[0][$key];

                    if (isset($tokens[$token])) {
                        continue;
                    }

                    $tokens[$token] = $this->getToday($modifier, $contactTimezone);
                }
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

        switch ($modifier) {
                case 'datetime':
                    $format = $defaultDatetimeFormat;
                    break;
                case 'date':
                    $format = $defaultDateFormat;
                    break;
                case 'time':
                    $format = $defaultTimeFormat;
                break;
                default:
                    $format  = $modifier ?: $defaultDatetimeFormat;
            }

        if ($relativeDate) {
            $dateTime->modify($relativeDate);
        }

        return $dateTime->format($format);
    }
}
