<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment;

use Symfony\Component\Translation\TranslatorInterface;

class RelativeDate
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @return array
     */
    public function getRelativeDateStrings(string $locale = null)
    {
        $keys = $this->getRelativeDateTranslationKeys();

        $strings = [];
        foreach ($keys as $key) {
            $strings[$key] = $this->translator->trans($key, [], null, $locale);
        }

        return $strings;
    }

    public function getParsedTimeFrame(string $filter): string
    {
        $key = array_key($filter, $this->getRelativeDateStrings(), true);
        $key = (false === $key) ? array_search($filter, $this->getRelativeDateStrings('en_US'), true) : $key;
        if (false === $key) {
            // Time frame does not match any option from $relativeDateStrings, so return original value
            return $filter;
        }

        return str_replace('mautic.lead.list.', '', $key);
    }

    /**
     * @return array
     */
    private function getRelativeDateTranslationKeys()
    {
        return [
            'mautic.lead.list.month_last',
            'mautic.lead.list.month_next',
            'mautic.lead.list.month_this',
            'mautic.lead.list.today',
            'mautic.lead.list.tomorrow',
            'mautic.lead.list.yesterday',
            'mautic.lead.list.week_last',
            'mautic.lead.list.week_next',
            'mautic.lead.list.week_this',
            'mautic.lead.list.year_last',
            'mautic.lead.list.year_next',
            'mautic.lead.list.year_this',
            'mautic.lead.list.anniversary',
        ];
    }
}
