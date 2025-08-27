<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Report;

use Mautic\LeadBundle\Entity\DoNotContact as DNC;
use Mautic\LeadBundle\Helper\DncFormatterHelper;
use Mautic\LeadBundle\Model\DoNotContact;

class DncReportService
{
    public const DEFAULT_DNC_OPTIONS = [
        ['reason' => DNC::UNSUBSCRIBED, 'channel' => 'email'],
        ['reason' => DNC::BOUNCED, 'channel' => 'email'],
        ['reason' => DNC::MANUAL, 'channel' => 'email'],
    ];

    public function __construct(
        private DoNotContact $doNotContactModel,
        private DncFormatterHelper $dncFormatterHelper,
    ) {
    }

    /**
     * Returns the configuration for DNC columns used in reports.
     *
     * @return array<string, array<string, string>> an associative array defining DNC columns, including alias, label, type, and SQL formula
     */
    public function getDncColumns(): array
    {
        return [
            'dnc_preferences' => [
                'alias'   => 'dnc_preferences',
                'label'   => 'mautic.lead.report.dnc_preferences',
                'type'    => 'string',
                'formula' => '(SELECT GROUP_CONCAT(CONCAT(dnc.reason, \':\', dnc.channel) ORDER BY dnc.date_added DESC SEPARATOR \',\') FROM '.MAUTIC_TABLE_PREFIX.'lead_donotcontact dnc WHERE dnc.lead_id = l.id)',
            ],
        ];
    }

    /**
     * Returns the configuration for DNC filters used in reports.
     *
     * @return array<string, array<string, mixed>> an associative array defining DNC filters, including label, type, list options, and operators
     */
    public function getDncFilters(): array
    {
        $dncOptions    = $this->doNotContactModel->getReasonChannelCombinations();
        $mergedOptions = array_unique(
            array_merge($dncOptions, self::DEFAULT_DNC_OPTIONS),
            SORT_REGULAR
        );

        $listOptions = [];
        foreach ($mergedOptions as $dncOption) {
            $key               = "{$dncOption['channel']}:{$dncOption['reason']}";
            $label             = $this->dncFormatterHelper->printReasonWithChannel($dncOption['reason'], $dncOption['channel']);
            $listOptions[$key] = $label;
        }

        return [
            'dnc_preferences' => [
                'label'     => 'mautic.lead.report.dnc_preferences',
                'type'      => 'multiselect',
                'list'      => $listOptions,
                'operators' => [
                    'in'       => 'mautic.core.operator.in',
                    'notIn'    => 'mautic.core.operator.notin',
                    'empty'    => 'mautic.core.operator.isempty',
                    'notEmpty' => 'mautic.core.operator.isnotempty',
                ],
            ],
        ];
    }

    /**
     * Processes and formats the DNC status display for each entry in the data array.
     *
     * @param array<int, array<string, mixed>> $data an array of data rows, each containing a 'dnc_preferences' key
     *
     * @return array<int, array<string, mixed>> the modified data array with formatted 'dnc_preferences' entries
     */
    public function processDncStatusDisplay(array $data): array
    {
        if (empty($data) || !array_key_exists('dnc_preferences', $data[0])) {
            return $data;
        }

        foreach ($data as &$row) {
            if (!empty($row['dnc_preferences'])) {
                $dncEntries = explode(',', $row['dnc_preferences']);
                $dncText    = array_map(function ($entry) {
                    list($reason, $channel) = explode(':', $entry);

                    return $this->dncFormatterHelper->printReasonWithChannel((int) $reason, $channel);
                }, $dncEntries);

                $row['dnc_preferences'] = implode(', ', $dncText);
            }
        }

        return $data;
    }
}
