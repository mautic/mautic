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
use Mautic\ReportBundle\Entity\Report;

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
     * Constructor.
     *
     * @param string $context Event context
     */
    public function __construct(Report $report, array $options, QueryBuilder $qb)
    {
        $this->report       = $report;
        $this->context      = $report->getSource();
        $this->options      = $options;
        $this->queryBuilder = $qb;
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
        $queryBuilder->leftJoin($prefix, MAUTIC_TABLE_PREFIX.'categories', $categoryPrefix, 'c.id = '.$prefix.'.category_id');

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
        $queryBuilder->leftJoin($prefix, MAUTIC_TABLE_PREFIX.'leads', $leadPrefix, 'l.id = '.$prefix.'.lead_id');

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
        $queryBuilder->leftJoin($prefix, MAUTIC_TABLE_PREFIX.'ip_addresses', $ipPrefix, 'i.id = '.$prefix.'.ip_id');

        return $this;
    }

    /**
     * Apply date filters to the query.
     *
     * @param QueryBuilder $query
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
            $queryBuilder->andWhere('DATE('.$tablePrefix.$dateColumn.') BETWEEN :dateFrom AND :dateTo');
            $queryBuilder->setParameter('dateFrom', $this->options['dateFrom']->format('Y-m-d'));
            $queryBuilder->setParameter('dateTo', $this->options['dateTo']->format('Y-m-d'));
        } else {
            $queryBuilder->andWhere($tablePrefix.$dateColumn.' BETWEEN :dateFrom AND :dateTo');
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
     * @return string
     */
    public function createParameterName()
    {
        $alpha_numeric = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

        return substr(str_shuffle($alpha_numeric), 0, 8);
    }
}
