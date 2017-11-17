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

use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Model\ReportModel;

/**
 * Class ReportGeneratorEvent.
 */
class ReportGeneratorEvent extends AbstractReportEvent
{
    /**
     * @var array
     */
    private $selectColumns = [];

    /**
     * QueryBuilder object.
     *
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * contentTemplate.
     *
     * @var string
     */
    private $contentTemplate;

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var ExpressionBuilder|null
     */
    private $filterExpression = null;

    /**
     * @var ChannelListHelper
     */
    private $channelListHelper;

    /**
     * ReportGeneratorEvent constructor.
     *
     * @param Report            $report
     * @param array             $options
     * @param QueryBuilder      $qb
     * @param ChannelListHelper $channelListHelper
     */
    public function __construct(Report $report, array $options, QueryBuilder $qb, ChannelListHelper $channelListHelper)
    {
        $this->report            = $report;
        $this->context           = $report->getSource();
        $this->options           = $options;
        $this->queryBuilder      = $qb;
        $this->channelListHelper = $channelListHelper;
    }

    /**
     * Fetch the QueryBuilder object.
     *
     * @return QueryBuilder
     *
     * @throws \RuntimeException
     */
    public function getQueryBuilder()
    {
        if ($this->queryBuilder instanceof QueryBuilder) {
            return $this->queryBuilder;
        }

        throw new \RuntimeException('QueryBuilder not set.');
    }

    /**
     * Set the QueryBuilder object.
     *
     * @param QueryBuilder $queryBuilder
     *
     * @return $this
     */
    public function setQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;

        return $this;
    }

    /**
     * Fetch the ContentTemplate path.
     *
     * @return QueryBuilder
     *
     * @throws \RuntimeException
     */
    public function getContentTemplate()
    {
        if ($this->contentTemplate) {
            return $this->contentTemplate;
        }

        // Default content template
        return 'MauticReportBundle:Report:details_data.html.php';
    }

    /**
     * Set the ContentTemplate path.
     *
     * @param string $contentTemplate
     *
     * @return $this
     */
    public function setContentTemplate($contentTemplate)
    {
        $this->contentTemplate = $contentTemplate;

        return $this;
    }

    /**
     * @return array
     */
    public function getSelectColumns()
    {
        return $this->selectColumns;
    }

    /**
     * Set custom select columns with aliases based on report settings.
     *
     * @param array $selectColumns
     *
     * @return $this
     */
    public function setSelectColumns(array $selectColumns)
    {
        $this->selectColumns = $selectColumns;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * @return ExpressionBuilder|null
     */
    public function getFilterExpression()
    {
        return $this->filterExpression;
    }

    /**
     * @param ExpressionBuilder $filterExpression
     *
     * @return $this
     */
    public function setFilterExpression(ExpressionBuilder $filterExpression)
    {
        $this->filterExpression = $filterExpression;

        return $this;
    }

    /**
     * Add category left join.
     *
     * @param QueryBuilder $queryBuilder
     * @param              $prefix
     *
     * @return $this
     */
    public function addCategoryLeftJoin(QueryBuilder $queryBuilder, $prefix, $categoryPrefix = 'c')
    {
        $queryBuilder->leftJoin($prefix, MAUTIC_TABLE_PREFIX.'categories', $categoryPrefix, $categoryPrefix.'.id = '.$prefix.'.category_id');

        return $this;
    }

    /**
     * Add lead left join.
     *
     * @param QueryBuilder $queryBuilder
     * @param              $prefix
     * @param string       $leadPrefix
     *
     * @return $this
     */
    public function addLeadLeftJoin(QueryBuilder $queryBuilder, $prefix, $leadPrefix = 'l')
    {
        $queryBuilder->leftJoin($prefix, MAUTIC_TABLE_PREFIX.'leads', $leadPrefix, $leadPrefix.'.id = '.$prefix.'.lead_id');

        return $this;
    }

    /**
     * Add IP left join.
     *
     * @param QueryBuilder $queryBuilder
     * @param              $prefix
     * @param string       $ipPrefix
     *
     * @return $this
     */
    public function addIpAddressLeftJoin(QueryBuilder $queryBuilder, $prefix, $ipPrefix = 'i')
    {
        $queryBuilder->leftJoin($prefix, MAUTIC_TABLE_PREFIX.'ip_addresses', $ipPrefix, $ipPrefix.'.id = '.$prefix.'.ip_id');

        return $this;
    }

    /**
     * Add IP left join.
     *
     * @param QueryBuilder $queryBuilder
     * @param              $prefix
     * @param string       $ipPrefix
     * @param string       $leadPrefix
     *
     * @return $this
     */
    public function addCampaignByChannelJoin(QueryBuilder $queryBuilder, $prefix, $channel, $leadPrefix = 'l')
    {
        $options = $this->getOptions();
        $cmpName = 'cmp.name';
        $cmpId   = 'clel.campaign_id';

        if ($this->hasColumn($cmpName)
            || $this->hasFilter($cmpName)
            || $this->hasColumn($cmpId)
            || $this->hasFilter($cmpId)
            || (!empty($options['order'][0]
                    && ($options['order'][0] === $cmpName
                        || $options['order'][0] === $cmpId)))) {
            $queryBuilder->leftJoin($prefix, MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'clel', sprintf('clel.channel="%s" AND %s.id = clel.channel_id AND clel.lead_id = %s.id', $channel, $prefix, $leadPrefix))
                    ->leftJoin('clel', MAUTIC_TABLE_PREFIX.'campaigns', 'cmp', 'cmp.id = clel.campaign_id');
        }

        return $this;
    }

    /**
     * Join channel columns.
     *
     * @param QueryBuilder $queryBuilder
     * @param              $prefix
     *
     * @return $this
     */
    public function addChannelLeftJoins(QueryBuilder $queryBuilder, $prefix)
    {
        foreach ($this->channelListHelper->getChannels() as $channel => $details) {
            if (!array_key_exists(ReportModel::CHANNEL_FEATURE, $details)) {
                continue;
            }

            $reportDetails = $details[ReportModel::CHANNEL_FEATURE];

            if (!array_key_exists('table', $reportDetails)) {
                continue;
            }

            $channelParameter = 'channelParameter'.$channel;

            $queryBuilder->leftJoin(
                $prefix,
                MAUTIC_TABLE_PREFIX.$reportDetails['table'],
                $channel,
                $prefix.'.channel_id = '.$channel.'.id AND '.$prefix.'.channel = :'.$channelParameter
            );

            $queryBuilder->setParameter($channelParameter, $channel);
        }

        return $this;
    }

    /**
     * Apply date filters to the query.
     *
     * @param QueryBuilder $queryBuilder
     * @param string       $dateColumn
     * @param string       $tablePrefix
     *
     * @return $this
     */
    public function applyDateFilters(QueryBuilder $queryBuilder, $dateColumn, $tablePrefix = 't', $dateOnly = false)
    {
        if ($tablePrefix) {
            $tablePrefix .= '.';
        }

        if (empty($this->options['dateFrom'])) {
            $this->options['dateFrom'] = new \DateTime();
            $this->options['dateFrom']->modify('-30 days');
        }

        if (empty($this->options['dateTo'])) {
            $this->options['dateTo'] = new \DateTime();
        }

        if ($dateOnly) {
            $queryBuilder->andWhere(sprintf('%1$s IS NULL OR (DATE(%1$s) BETWEEN :dateFrom AND :dateTo)', $tablePrefix.$dateColumn));
            $queryBuilder->setParameter('dateFrom', $this->options['dateFrom']->format('Y-m-d'));
            $queryBuilder->setParameter('dateTo', $this->options['dateTo']->format('Y-m-d'));
        } else {
            $queryBuilder->andWhere(sprintf('%1$s IS NULL OR (%1$s BETWEEN :dateFrom AND :dateTo)', $tablePrefix.$dateColumn));
            $queryBuilder->setParameter('dateFrom', $this->options['dateFrom']->format('Y-m-d H:i:s'));
            $queryBuilder->setParameter('dateTo', $this->options['dateTo']->format('Y-m-d H:i:s'));
        }

        return $this;
    }

    /**
     * Check if the report has a specific column.
     *
     * @param $column
     *
     * @return bool
     */
    public function hasColumn($column)
    {
        static $sorted;

        if (null == $sorted) {
            $columns = $this->getReport()->getColumns();

            foreach ($columns as $field) {
                $sorted[$field] = true;
            }
        }

        if (is_array($column)) {
            foreach ($column as $checkMe) {
                if (isset($sorted[$checkMe])) {
                    return true;
                }
            }

            return false;
        }

        return isset($sorted[$column]);
    }

    /**
     * Check if the report has a specific filter.
     *
     * @param $column
     *
     * @return bool
     */
    public function hasFilter($column)
    {
        static $sorted;

        if (null == $sorted) {
            $filters = $this->getReport()->getFilters();

            foreach ($filters as $field) {
                $sorted[$field['column']] = true;
            }
        }

        if (is_array($column)) {
            foreach ($column as $checkMe) {
                if (isset($sorted[$checkMe])) {
                    return true;
                }
            }

            return false;
        }

        return isset($sorted[$column]);
    }

    /**
     * Check if the report has a groupBy columns selected.
     *
     *
     * @return bool
     */
    public function hasGroupBy()
    {
        if (!empty($this->getReport()->getGroupBy())) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function createParameterName()
    {
        $alpha_numeric = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        return substr(str_shuffle($alpha_numeric), 0, 8);
    }
}
