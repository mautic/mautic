<?php

namespace Mautic\ReportBundle\Event;

use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\ReportBundle\Builder\MauticReportBuilder;
use Mautic\ReportBundle\Helper\ReportHelper;
use Mautic\ReportBundle\Model\ReportModel;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ReportBuilderEvent.
 */
class ReportBuilderEvent extends AbstractReportEvent
{
    /**
     * Container with registered tables and columns.
     *
     * @var array
     */
    private $tableArray = [];

    /**
     * Supported graphs.
     *
     * @var array
     */
    private $supportedGraphs = [
        'table',
        'bar',
        'pie',
        'line',
    ];

    /**
     * @var ChannelListHelper
     */
    private $channelListHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Container with registered graphs.
     *
     * @var array
     */
    private $graphArray = [];

    /**
     * List of published array of lead fields.
     *
     * @var array
     */
    private $leadFields = [];

    private $reportHelper;

    /**
     * ReportBuilderEvent constructor.
     *
     * @param string $context
     */
    public function __construct(TranslatorInterface $translator, ChannelListHelper $channelListHelper, $context, $leadFields, ReportHelper $reportHelper)
    {
        $this->context           = $context;
        $this->translator        = $translator;
        $this->channelListHelper = $channelListHelper;
        $this->leadFields        = $leadFields;
        $this->reportHelper      = $reportHelper;
    }

    /**
     * Add a table with the specified columns to the lookup.
     *
     * The data should be an associative array with the following data:
     * 'display_name' => The translation key to display in the select list
     * 'columns'      => An array containing the table's columns
     *
     * @param string $context Context for data
     * @param array  $data    Data array for the table
     *
     * @return ReportBuilderEvent
     */
    public function addTable($context, array $data, $group = null)
    {
        $data['group'] = (null == $group) ? $context : $group;

        foreach ($data['columns'] as $column => &$d) {
            $d['label'] = $this->translator->trans($d['label']);
            if (!isset($d['alias'])) {
                $d['alias'] = substr(
                    $column,
                    false !== ($pos = strpos($column, '.')) ? $pos + 1 : 0
                );
            }
        }

        uasort(
            $data['columns'],
            function ($a, $b) {
                return strnatcmp($a['label'], $b['label']);
            }
        );

        if (isset($data['filters'])) {
            foreach ($data['filters'] as $column => &$d) {
                $d['label'] = $this->translator->trans($d['label']);
                if (!isset($d['alias'])) {
                    $d['alias'] = substr(
                        $column,
                        false !== ($pos = strpos($column, '.')) ? $pos + 1 : 0
                    );
                }
            }

            uasort(
                $data['filters'],
                function ($a, $b) {
                    return strnatcmp($a['label'], $b['label']);
                }
            );
        }

        $this->tableArray[$context] = $data;

        if ($this->context == $context) {
            $this->stopPropagation();
        }

        return $this;
    }

    /**
     * Fetch the tables in the lookup array.
     *
     * @return array
     */
    public function getTables()
    {
        return $this->tableArray;
    }

    /**
     * Returns standard form fields such as id, name, publish_up, etc.
     *
     * @param string $prefix
     *
     * @return string[]
     */
    public function getStandardColumns($prefix, $removeColumns = [], $idLink = null)
    {
        return $this->reportHelper->getStandardColumns($prefix, $removeColumns, (string) $idLink);
    }

    /**
     * Returns lead columns.
     *
     * @param $prefix
     *
     * @return array
     */
    public function getLeadColumns($prefix = 'l.')
    {
        $fields = [];

        foreach ($this->leadFields as $fieldArray) {
            $fields[$prefix.$fieldArray['alias']] = [
                'label' => $this->translator->trans('mautic.report.field.lead.label', ['%field%' => $fieldArray['label']]),
                'type'  => $this->reportHelper->getReportBuilderFieldType($fieldArray['type']),
                'alias' => $fieldArray['alias'],
            ];
        }
        $fields[$prefix.'id'] = [
            'label' => 'mautic.report.field.lead.id',
            'type'  => 'int',
            'link'  => 'mautic_contact_action',
            'alias' => 'contactId',
        ];

        return $fields;
    }

    /**
     * Get IP Address column.
     *
     * @param string $prefix
     *
     * @return array
     */
    public function getIpColumn($prefix = 'i.')
    {
        return [
            $prefix.'ip_address' => [
                'label' => 'mautic.core.ipaddress',
                'type'  => 'string',
            ],
        ];
    }

    /**
     * Add category columns.
     *
     * @param string $prefix
     *
     * @return array
     */
    public function getCategoryColumns($prefix = 'c.')
    {
        return [
            $prefix.'id' => [
                'label' => 'mautic.report.field.category_id',
                'type'  => 'int',
                'alias' => 'category_id',
            ],
            $prefix.'title' => [
                'label' => 'mautic.report.field.category_name',
                'type'  => 'string',
                'alias' => 'category_title',
            ],
        ];
    }

    /**
     * Add campaign columns joined by the campaign lead event log table.
     *
     * @return array
     */
    public function getCampaignByChannelColumns()
    {
        return [
            'clel.campaign_id' => [
                'label' => 'mautic.campaign.campaign.id',
                'type'  => 'string',
            ],
            'cmp.name' => [
                'label' => 'mautic.campaign.campaign',
                'type'  => 'string',
            ],
        ];
    }

    public function getChannelColumns()
    {
        $channelColumns = [
            MauticReportBuilder::CHANNEL_COLUMN_CATEGORY_ID => [
                'label'       => 'mautic.report.campaign.channel.category_id',
                'type'        => 'int',
                'alias'       => 'channel_category_id',
                'channelData' => [],
            ],
            MauticReportBuilder::CHANNEL_COLUMN_CREATED_BY => [
                'label'       => 'mautic.report.campaign.channel.created_by',
                'type'        => 'int',
                'alias'       => 'channel_created_by',
                'channelData' => [],
            ],
            MauticReportBuilder::CHANNEL_COLUMN_CREATED_BY_USER => [
                'label'       => 'mautic.report.campaign.channel.created_by_user',
                'type'        => 'string',
                'alias'       => 'channel_created_by_user',
                'channelData' => [],
            ],
            MauticReportBuilder::CHANNEL_COLUMN_DATE_ADDED => [
                'label'       => 'mautic.report.campaign.channel.date_added',
                'type'        => 'datetime',
                'alias'       => 'channel_date_added',
                'channelData' => [],
            ],
            MauticReportBuilder::CHANNEL_COLUMN_DESCRIPTION => [
                'label'       => 'mautic.report.campaign.channel.description',
                'type'        => 'string',
                'alias'       => 'channel_description',
                'channelData' => [],
            ],
            MauticReportBuilder::CHANNEL_COLUMN_NAME => [
                'label'       => 'mautic.report.campaign.channel.name',
                'type'        => 'string',
                'alias'       => 'channel_name',
                'channelData' => [],
            ],
        ];

        foreach ($this->channelListHelper->getChannels() as $channel => $details) {
            if (!array_key_exists(ReportModel::CHANNEL_FEATURE, $details)) {
                continue;
            }

            $reportDetails = $details[ReportModel::CHANNEL_FEATURE];

            $hasFields = array_key_exists('fields', $reportDetails) && is_array($reportDetails['fields']);

            foreach ($channelColumns as $column => $definition) {
                $channelColumnName = $hasFields && array_key_exists($column, $reportDetails['fields'])
                    ? $reportDetails['fields'][$column]
                    : str_replace('channel.', $channel.'.', $column);

                $channelColumns[$column]['channelData'][$channel] = [
                    'prefix' => $channel,
                    'column' => $channelColumnName,
                ];
            }
        }

        return $channelColumns;
    }

    /**
     * @param       $context
     * @param       $type
     * @param       $graphId
     * @param array $options
     *
     * @return $this
     */
    public function addGraph($context, $type, $graphId, $options = [])
    {
        if (in_array($type, $this->supportedGraphs)) {
            $this->graphArray[$context][$graphId] = [
                'options' => $options,
                'type'    => $type,
            ];
        }

        return $this;
    }

    /**
     * Get graphs.
     *
     * @return array
     */
    public function getGraphs()
    {
        return $this->graphArray;
    }
}
