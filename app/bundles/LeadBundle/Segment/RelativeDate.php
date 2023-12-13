<?php

namespace Mautic\LeadBundle\Segment;

use Symfony\Contracts\Translation\TranslatorInterface;

class RelativeDate
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    public function getRelativeDateStrings(): array
    {
        $keys = $this->getRelativeDateTranslationKeys();

        $strings = [];
        foreach ($keys as $key) {
            $strings[$key] = $this->translator->trans($key);
        }

        return $strings;
    }

    private function getRelativeDateTranslationKeys(): array
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
