<?php

namespace Mautic\ReportBundle\Event;

use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Model\ReportModel;

class ReportGeneratorEvent extends AbstractReportEvent
{
    public const CATEGORY_PREFIX         = 'c';

    public const CONTACT_PREFIX          = 'l';

    public const COMPANY_PREFIX          = 'comp';

    public const COMPANY_LEAD_PREFIX     = 'companies_lead';

    public const IP_ADDRESS_PREFIX       = 'i';

    private array $selectColumns = [];

    private ?string $contentTemplate = null;

    private ?ExpressionBuilder $filterExpression = null;

    private ?array $sortedFilters = null;

    public function __construct(
        Report $report,
        private array $options,
        private QueryBuilder $queryBuilder,
        private ChannelListHelper $channelListHelper,
    ) {
        $this->report            = $report;
        $this->context           = $report->getSource();
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function setQueryBuilder(QueryBuilder $queryBuilder): self
    {
        $this->queryBuilder = $queryBuilder;

        return $this;
    }

    public function getContentTemplate(): ?string
    {
        if ($this->contentTemplate) {
            return $this->contentTemplate;
        }

        // Default content template
        return '@MauticReport/Report/details.html.twig';
    }

    public function setContentTemplate(?string $contentTemplate): self
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
     */
    public function setSelectColumns(array $selectColumns): self
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

    public function getFilterExpression(): ?ExpressionBuilder
    {
        return $this->filterExpression;
    }

    public function setFilterExpression(ExpressionBuilder $filterExpression): self
    {
        $this->filterExpression = $filterExpression;

        return $this;
    }

    /**
     * Add category left join.
     *
     * @param string $prefix
     * @param string $categoryPrefix
     */
    public function addCategoryLeftJoin(QueryBuilder $queryBuilder, $prefix, $categoryPrefix = self::CATEGORY_PREFIX): self
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
     */
    public function addLeadLeftJoin(QueryBuilder $queryBuilder, $prefix, $leadPrefix = self::CONTACT_PREFIX): self
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
     */
    public function addIpAddressLeftJoin(QueryBuilder $queryBuilder, $prefix, $ipPrefix = self::IP_ADDRESS_PREFIX): self
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
     */
    public function addLeadIpAddressLeftJoin(QueryBuilder $queryBuilder, $ipXrefPrefix = 'lip', $ipPrefix = self::IP_ADDRESS_PREFIX, $leadPrefix = self::CONTACT_PREFIX): self
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
     */
    public function addCampaignByChannelJoin(QueryBuilder $queryBuilder, $prefix, $channel, $leadPrefix = self::CONTACT_PREFIX, $onColumn = 'id'): self
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
     */
    public function addChannelLeftJoins(QueryBuilder $queryBuilder, $prefix): self
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
    public function addCompanyLeftJoin(QueryBuilder $queryBuilder, string $companyPrefix = self::COMPANY_PREFIX, string $contactPrefix = self::CONTACT_PREFIX): void
    {
        if ($this->usesColumnWithPrefix($companyPrefix) || $this->usesColumnWithPrefix(self::COMPANY_LEAD_PREFIX)) {
            if ($this->isJoined($queryBuilder, MAUTIC_TABLE_PREFIX.'companies_leads', 'l', self::COMPANY_LEAD_PREFIX)) {
                return;
            }
            $queryBuilder->leftJoin('l', MAUTIC_TABLE_PREFIX.'companies_leads', self::COMPANY_LEAD_PREFIX, $contactPrefix.'.id ='.self::COMPANY_LEAD_PREFIX.'.lead_id');
            $queryBuilder->leftJoin(self::COMPANY_LEAD_PREFIX, MAUTIC_TABLE_PREFIX.'companies', $companyPrefix, self::COMPANY_LEAD_PREFIX.'.company_id = '.$companyPrefix.'.id');
        }
    }

    /**
     * Apply date filters to the query.
     *
     * @param string $dateColumn
     * @param string $tablePrefix
     * @param bool   $dateOnly
     *
     * @throws \Exception
     */
    public function applyDateFilters(QueryBuilder $queryBuilder, $dateColumn, $tablePrefix = 't', $dateOnly = false): ReportGeneratorEvent
    {
        $this->setDateRangeQueryFilters(
            $queryBuilder, $tablePrefix, $dateOnly, $dateColumn,
            '%1$s IS NULL OR (DATE(%1$s) BETWEEN :dateFrom AND :dateTo)',
            '%1$s IS NULL OR (%1$s BETWEEN :dateFrom AND :dateTo)'
        );

        return $this;
    }

    public function applyDateFiltersWithoutNullValues(QueryBuilder $queryBuilder, string $dateColumn, string $tablePrefix = 't', bool $dateOnly = false): ReportGeneratorEvent
    {
        $this->setDateRangeQueryFilters(
            $queryBuilder, $tablePrefix, $dateOnly, $dateColumn,
            'DATE(%1$s) BETWEEN :dateFrom AND :dateTo',
            '%1$s BETWEEN :dateFrom AND :dateTo'
        );

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
     */
    public function hasColumn($column): bool
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
     */
    public function hasFilter($column): bool
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
     * @throws \UnexpectedValueException
     */
    public function getFilterValues($column): array
    {
        return $this->getReport()->getFilterValues($column);
    }

    /**
     * Check if the report has a groupBy columns selected.
     */
    public function hasGroupBy(): bool
    {
        if (!empty($this->getReport()->getGroupBy())) {
            return true;
        }

        return false;
    }

    public function createParameterName(): string
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

    private function setDateRangeQueryFilters(QueryBuilder $queryBuilder, string $tablePrefix, bool $dateOnly, string $dateColumn, string $dateOnlyFilter, string $dateTimeFilter): void
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
            $queryBuilder->andWhere(sprintf($dateOnlyFilter, $tablePrefix.$dateColumn));
            $queryBuilder->setParameter('dateFrom', $this->options['dateFrom']->format('Y-m-d'));
            $queryBuilder->setParameter('dateTo', $this->options['dateTo']->format('Y-m-d'));
        } else {
            $queryBuilder->andWhere(sprintf($dateTimeFilter, $tablePrefix.$dateColumn));
            $queryBuilder->setParameter('dateFrom', $this->options['dateFrom']->format('Y-m-d H:i:s'));
            $queryBuilder->setParameter('dateTo', $this->options['dateTo']->format('Y-m-d H:i:s'));
        }
    }

    private function isJoined(QueryBuilder $query, string $table, string $fromAlias, string $alias): bool
    {
        $queryParts = $query->getQueryParts();
        $joins      =   !empty($queryParts) && $queryParts['join'] ? $queryParts['join'] : null;
        if (empty($joins) || (!empty($joins) && empty($joins[$fromAlias]))) { // @phpstan-ignore-line
            return false;
        }
        foreach ($joins[$fromAlias] as $join) {
            if ($join['joinTable'] == $table && $join['joinAlias'] == $alias) {
                return true;
            }
        }

        return false;
    }
}
