<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Event;

use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\ReportBundle\Builder\MauticReportBuilder;
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
     * ReportBuilderEvent constructor.
     *
     * @param TranslatorInterface $translator
     * @param ChannelListHelper   $channelListHelper
     * @param string              $context
     */
    public function __construct(TranslatorInterface $translator, ChannelListHelper $channelListHelper, $context = '')
    {
        $this->context           = $context;
        $this->translator        = $translator;
        $this->channelListHelper = $channelListHelper;
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
                    ($pos = strpos($column, '.')) !== false ? $pos + 1 : 0
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
                        ($pos = strpos($column, '.')) !== false ? $pos + 1 : 0
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
     * @param   $prefix
     *
     * @return array
     */
    public function getStandardColumns($prefix, $removeColumns = [], $idLink = null)
    {
        $aliasPrefix = str_replace('.', '_', $prefix);
        $columns     = [
            $prefix.'id' => [
                'label' => 'mautic.core.id',
                'type'  => 'int',
                'link'  => $idLink,
                'alias' => "{$aliasPrefix}id",
            ],
            $prefix.'name' => [
                'label' => 'mautic.core.name',
                'type'  => 'string',
                'alias' => "{$aliasPrefix}name",
            ],
            $prefix.'created_by_user' => [
                'label' => 'mautic.core.createdby',
                'type'  => 'string',
                'alias' => "{$aliasPrefix}created_by_user",
            ],
            $prefix.'date_added' => [
                'label' => 'mautic.report.field.date_added',
                'type'  => 'datetime',
                'alias' => "{$aliasPrefix}date_added",
            ],
            $prefix.'modified_by_user' => [
                'label' => 'mautic.report.field.modified_by_user',
                'type'  => 'string',
                'alias' => "{$aliasPrefix}modified_by_user",
            ],
            $prefix.'date_modified' => [
                'label' => 'mautic.report.field.date_modified',
                'type'  => 'datetime',
                'alias' => "{$aliasPrefix}date_modified",
            ],
            $prefix.'description' => [
                'label' => 'mautic.core.description',
                'type'  => 'string',
                'alias' => "{$aliasPrefix}description",
            ],
            $prefix.'publish_up' => [
                'label' => 'mautic.report.field.publish_up',
                'type'  => 'datetime',
                'alias' => "{$aliasPrefix}publish_up",
            ],
            $prefix.'publish_down' => [
                'label' => 'mautic.report.field.publish_down',
                'type'  => 'datetime',
                'alias' => "{$aliasPrefix}publish_down",
            ],
            $prefix.'is_published' => [
                'label' => 'mautic.report.field.is_published',
                'type'  => 'bool',
                'alias' => "{$aliasPrefix}is_published",
            ],
        ];

        if (empty($idLink)) {
            unset($columns[$prefix.'id']['link']);
        }

        if (!empty($removeColumns)) {
            foreach ($removeColumns as $c) {
                if (isset($columns[$prefix.$c])) {
                    unset($columns[$prefix.$c]);
                }
            }
        }

        return $columns;
    }

    /**
     * Returns lead columns.
     *
     * @param   $prefix
     *
     * @return array
     */
    public function getLeadColumns($prefix = 'l.')
    {
        return [
            $prefix.'id' => [
                'label' => 'mautic.report.field.lead.id',
                'type'  => 'int',
                'alias' => 'contact_id',
            ],
            $prefix.'title' => [
                'label' => 'mautic.report.field.lead.title',
                'type'  => 'string',
                'alias' => 'contact_title',
            ],
            $prefix.'firstname' => [
                'label' => 'mautic.report.field.lead.firstname',
                'type'  => 'string',
            ],
            $prefix.'lastname' => [
                'label' => 'mautic.report.field.lead.lastname',
                'type'  => 'string',
            ],
            $prefix.'email' => [
                'label' => 'mautic.report.field.lead.email',
                'type'  => 'string',
            ],
            $prefix.'company' => [
                'label' => 'mautic.report.field.lead.company',
                'type'  => 'string',
            ],
            $prefix.'position' => [
                'label' => 'mautic.report.field.lead.position',
                'type'  => 'string',
            ],
            $prefix.'phone' => [
                'label' => 'mautic.report.field.lead.phone',
                'type'  => 'string',
            ],
            $prefix.'mobile' => [
                'label' => 'mautic.report.field.lead.mobile',
                'type'  => 'string',
            ],
            $prefix.'address1' => [
                'label' => 'mautic.report.field.lead.address1',
                'type'  => 'string',
            ],
            $prefix.'address2' => [
                'label' => 'mautic.report.field.lead.address2',
                'type'  => 'string',
            ],
            $prefix.'country' => [
                'label' => 'mautic.report.field.lead.country',
                'type'  => 'string',
            ],
            $prefix.'city' => [
                'label' => 'mautic.report.field.lead.city',
                'type'  => 'string',
            ],
            $prefix.'state' => [
                'label' => 'mautic.report.field.lead.zipcode',
                'type'  => 'string',
            ],
        ];
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
