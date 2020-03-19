<?php
/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Query;

class SubQuery
{
    /**
     * @var array
     */
    private $subQueries = [];

    /**
     * @var string
     */
    private $table;

    /**
     * @return QueryBuilder
     */
    public function getSubQueries()
    {
        return $this->subQueries;
    }

    /**
     * @param QueryBuilder $subQueries
     */
    public function setSubQueries($subQueries)
    {
        $this->subQueries = $subQueries;
    }

    public function resetSubQueires()
    {
        $this->subQueries = [];
    }

    /**
     * @return bool
     */
    public function hasSubQuery()
    {
        if (isset($this->getSubQueries()[$this->table])) {
            return true;
        }

        return false;
    }

    /**
     * @return QueryBuilder
     */
    public function getSubQuery()
    {
        return $this->subQueries[$this->table];
    }

    /**
     * @param $subQueryBuilder
     */
    public function addSubQuery($subQueryBuilder)
    {
        $this->subQueries[$this->table] = $subQueryBuilder;
    }

    /**
     * @return string
     */
    public function getTableAlias()
    {
        /** @var QueryBuilder $subQueryBuilder */
        $subQueryBuilder = $this->getSubQuery();
        if (isset($subQueryBuilder->getQueryPart('from')[0]['alias'])) {
            return $subQueryBuilder->getQueryPart('from')[0]['alias'];
        }

        return '';
    }

    /**
     * @param string $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }
}
