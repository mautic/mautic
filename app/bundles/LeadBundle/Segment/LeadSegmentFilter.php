<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment;

class LeadSegmentFilter
{
    const LEAD_OBJECT    = 'lead';
    const COMPANY_OBJECT = 'company';

    /**
     * @var string|null
     */
    private $glue;

    /**
     * @var string|null
     */
    private $field;

    /**
     * @var string|null
     */
    private $object;

    /**
     * @var string|null
     */
    private $type;

    /**
     * @var string|array|null
     */
    private $filter;

    /**
     * @var string|null
     */
    private $display;

    /**
     * @var string|null
     */
    private $operator;

    /**
     * @var string
     */
    private $func;

    public function __construct(array $filter)
    {
        $this->glue     = isset($filter['glue']) ? $filter['glue'] : null;
        $this->field    = isset($filter['field']) ? $filter['field'] : null;
        $this->object   = isset($filter['object']) ? $filter['object'] : self::LEAD_OBJECT;
        $this->type     = isset($filter['type']) ? $filter['type'] : null;
        $this->filter   = isset($filter['filter']) ? $filter['filter'] : null;
        $this->display  = isset($filter['display']) ? $filter['display'] : null;
        $this->operator = isset($filter['operator']) ? $filter['operator'] : null;
    }

    /**
     * @return string|null
     */
    public function getGlue()
    {
        return $this->glue;
    }

    /**
     * @return string|null
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return string|null
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @return bool
     */
    public function isLeadType()
    {
        return $this->object === self::LEAD_OBJECT;
    }

    /**
     * @return bool
     */
    public function isCompanyType()
    {
        return $this->object === self::COMPANY_OBJECT;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string|array|null
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @return string|null
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * @return string|null
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @param string|null $operator
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
    }

    /**
     * @param string|array|null $filter
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
    }

    /**
     * @return string
     */
    public function getFunc()
    {
        return $this->func;
    }

    /**
     * @param string $func
     */
    public function setFunc($func)
    {
        $this->func = $func;
    }
}
