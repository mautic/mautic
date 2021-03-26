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

class ReportGeneratorEvent extends AbstractReportEvent
{
    const CATEGORY_PREFIX    = 'c';
    const CONTACT_PREFIX     = 'l';
    const COMPANY_PREFIX     = 'comp';
    const IP_ADDRESS_PREFIX  = 'i';

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
    private $filterExpression;

    /**
     * @var ChannelListHelper
     */
    private $channelListHelper;

    /**
     * @var array|null
     */
    private $sortedFilters;

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
     * @param string $prefix
     * @param string $categoryPrefix
     *
     * @return $this
     */
    public function addCategoryLeftJoin(QueryBuilder $queryBuilder, $prefix, $categoryPrefix = self::CATEGORY_PREFIX)
    {
        if ($this->usesColumnWithPrefix($categoryPrefix)) {
            $queryBuilder->leftJoin($prefix, MAUTIC_TABLE_PREFIX.'categories', $categoryPrefix, $categoryPrefix.'.id = '.$prefix.'.category_id');
        }

        return $this;
    }

    /**
     * Add lead left join.
     *
     * @param string $prefix
     * @param string $leadPrefix
     *
     * @return $this
     */
    public function addLeadLeftJoin(QueryBuilder $queryBuilder, $prefix, $leadPrefix = self::CONTACT_PREFIX)
    {
        if ($this->usesColumnWithPrefix($leadPrefix)
            || $this->usesColumnWithPrefix(self::IP_ADDRESS_PREFIX)
            || $this->usesColumnWithPrefix(self::COMPANY_PREFIX)
            || $this->usesColumn('cmp.name')
            || $this->usesColumn('clel.campaign_id')
        ) {
            $queryBuilder->leftJoin($prefix, MAUTIC_TABLE_PREFIX.'leads', $leadPrefix, $leadPrefix.'.id = '.$prefix.'.lead_id');
        }

        return $this;
    }

    /**
     * Add IP left join.
     *
     * @param string $prefix
     * @param string $ipPrefix
     *
     * @return $this
     */
    public function addIpAddressLeftJoin(QueryBuilder $queryBuilder, $prefix, $ipPrefix = self::IP_ADDRESS_PREFIX)
    {
        if ($this->usesColumnWithPrefix($ipPrefix)) {
            $queryBuilder->leftJoin($prefix, MAUTIC_TABLE_PREFIX.'ip_addresses', $ipPrefix, $ipPrefix.'.id = '.$prefix.'.ip_id');
        }

        return $this;
    }

    /**
     * Add IP left join with lead join.
     *
     * @param string $ipXrefPrefix
     * @param string $ipPrefix
     * @param string $leadPrefix
     *
     * @return $this
     */
    public function addLeadIpAddressLeftJoin(QueryBuilder $queryBuilder, $ipXrefPrefix = 'lip', $ipPrefix = self::IP_ADDRESS_PREFIX, $leadPrefix = self::CONTACT_PREFIX)
    {
        if ($this->usesColumnWithPrefix($ipPrefix)) {
            $this->addIpAddressLeftJoin($queryBuilder, $ipXrefPrefix, $ipPrefix);
            $queryBuilder->leftJoin($leadPrefix, MAUTIC_TABLE_PREFIX.'lead_ips_xref', $ipXrefPrefix, $ipXrefPrefix.'.lead_id = '.$leadPrefix.'.id');
        }

        return $this;
    }

    /**
     * Add IP left join.
     *
     * @param string $prefix
     * @param string $channel
     * @param string $leadPrefix
     * @param string $onColumn
     *
     * @return $this
     */
    public function addCampaignByChannelJoin(QueryBuilder $queryBuilder, $prefix, $channel, $leadPrefix = self::CONTACT_PREFIX, $onColumn = 'id')
    {
        if ($this->usesColumn('cmp.name') || $this->usesColumn('clel.campaign_id')) {
            $condition = "clel.channel='{$channel}' AND {$prefix}.{$onColumn} = clel.channel_id AND clel.lead_id = {$leadPrefix}.id";
            $queryBuilder->leftJoin($prefix, MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'clel', $condition);
            $queryBuilder->leftJoin('clel', MAUTIC_TABLE_PREFIX.'campaigns', 'cmp', 'cmp.id = clel.campaign_id');
        }

        return $this;
    }

    /**
     * Join channel columns.
     *
     * @param string $prefix
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
     * Add company left join.
     */
    public function addCompanyLeftJoin(QueryBuilder $queryBuilder, $companyPrefix = self::COMPANY_PREFIX, $contactPrefix = self::CONTACT_PREFIX)
    {
        if ($this->usesColumnWithPrefix($companyPrefix)) {
            $queryBuilder->leftJoin('l', MAUTIC_TABLE_PREFIX.'companies_leads', 'companies_lead', $contactPrefix.'.id = companies_lead.lead_id');
            $queryBuilder->leftJoin('companies_lead', MAUTIC_TABLE_PREFIX.'companies', $companyPrefix, 'companies_lead.company_id = '.$companyPrefix.'.id');
        }
    }

    /**
     * Apply date filters to the query.
     *
     * @param string $dateColumn
     * @param string $tablePrefix
     * @param bool   $dateOnly
     *
     * @return $this
     *
     * @throws \Exception
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

    public function hasColumnWithPrefix(string $prefix): bool
    {
        $columns = $this->getReport()->getSelectAndAggregatorAndOrderAndGroupByColumns();
        $pattern = "/^{$prefix}\./";

        return count(preg_grep($pattern, $columns)) > 0;
    }

    /**
     * Returns true if the report uses the column anywhere in the query.
     *
     * @param string|array $column
     */
    public function usesColumn($column): bool
    {
        return $this->hasColumn($column) || $this->hasFilter($column);
    }

    /**
     * Returns true if the report uses the prefix anywhere in the query.
     */
    public function usesColumnWithPrefix(string $prefix): bool
    {
        if ($this->hasColumnWithPrefix($prefix)) {
            return true;
        }

        $this->buildSortedFilters();

        $pattern = "/^{$prefix}\./";

        return count(preg_grep($pattern, array_keys($this->sortedFilters))) > 0;
    }

    /**
     * Check if the report has a specific column.
     *
     * @param array|string $column
     *
     * @return bool
     */
    public function hasColumn($column)
    {
        $columns = $this->getReport()->getSelectAndAggregatorAndOrderAndGroupByColumns();

        if (is_array($column)) {
            foreach ($column as $checkMe) {
                if (in_array($checkMe, $columns, true)) {
                    return true;
                }
            }

            return false;
        }

        return in_array($column, $columns, true);
    }

    /**
     * Check if the report has a specific filter.
     *
     * @param array|string $column
     *
     * @return bool
     */
    public function hasFilter($column)
    {
        $this->buildSortedFilters();

        if (is_array($column)) {
            foreach ($column as $checkMe) {
                if (isset($this->sortedFilters[$checkMe])) {
                    return true;
                }
            }

            return false;
        }

        return isset($this->sortedFilters[$column]);
    }

    /**
     * Get filter value from a specific filter.
     *
     * @param string $column
     *
     * @return mixed
     *
     * @throws \UnexpectedValueException
     */
    public function getFilterValue($column)
    {
        return $this->getReport()->getFilterValue($column);
    }

    /**
     * Get filter values from a specific filter.
     *
     * @param string $column
     *
     * @return array
     *
     * @throws \UnexpectedValueException
     */
    public function getFilterValues($column)
    {
        return $this->getReport()->getFilterValues($column);
    }

    /**
     * Check if the report has a groupBy columns selected.
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

    private function buildSortedFilters(): void
    {
        if (null !== $this->sortedFilters) {
            return;
        }

        $this->sortedFilters = [];
        $filters             = (array) $this->getReport()->getFilters();

        foreach ($filters as $field) {
            $this->sortedFilters[$field['column']] = true;
        }
    }
}
