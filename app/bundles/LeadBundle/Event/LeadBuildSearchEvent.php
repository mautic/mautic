<?php

/*
 * @copyright  2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Event;

use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Event\CommonEvent;

/**
 * Class LeadBuildSearchEvent.
 */
class LeadBuildSearchEvent extends CommonEvent
{
    /**
     * @var string
     */
    protected $string;

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var string
     */
    protected $alias;

    /**
     * @var string
     */
    protected $command;

    /**
     * @var string
     */
    protected $subQuery;

    /**
     * @var bool
     */
    protected $negate;

    /**
     * @var bool
     */
    protected $isSearchDone;

    /**
     * @var bool
     */
    protected $returnParameters;

    /**
     * @var bool
     */
    protected $strict;

    /**
     * @var array
     */
    protected $parameters;

    /**
     * @param string       $string
     * @param string       $command
     * @param string       $alias
     * @param string       $negate
     * @param QueryBuilder $queryBuilder
     */
    public function __construct($string, $command, $alias, $negate, QueryBuilder $queryBuilder)
    {
        $this->string           = $string;
        $this->command          = $command;
        $this->alias            = $alias;
        $this->negate           = $negate;
        $this->queryBuilder     = $queryBuilder;
        $this->subQuery         = '';
        $this->isSearchDone     = false;
        $this->strict           = false;
        $this->returnParameters = false;
        $this->parameters       = [];
    }

    /**
     * @return string
     */
    public function getString()
    {
        return $this->string;
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return bool
     */
    public function isNegation()
    {
        return $this->negate;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @param bool $status
     */
    public function setSearchStatus($status)
    {
        $this->isSearchDone = $status;
    }

    /**
     * @param string $query
     */
    public function setSubQuery($query)
    {
        $this->subQuery = $query;

        $this->setSearchStatus(true);
    }

    /**
     * @return bool
     */
    public function isSearchDone()
    {
        return $this->isSearchDone;
    }

    /**
     * @return string
     */
    public function getSubQuery()
    {
        return $this->subQuery;
    }

    /**
     * @param array $string
     */
    public function setString($string)
    {
        $this->string = $string;
    }

    /**
     * @return bool
     */
    public function getStrict()
    {
        return $this->strict;
    }

    /**
     * @param bool $val
     */
    public function setStrict($val)
    {
        $this->strict = $val;
    }

    /**
     * @return bool
     */
    public function getReturnParameters()
    {
        return $this->returnParameters;
    }

    /**
     * @param bool $val
     */
    public function setReturnParameters($val)
    {
        $this->returnParameters = $val;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $val
     */
    public function setParameters($val)
    {
        $this->parameters = $val;
    }
}
