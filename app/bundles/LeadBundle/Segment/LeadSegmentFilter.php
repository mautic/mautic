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

use Mautic\LeadBundle\Services\LeadSegmentFilterDescriptor;

class LeadSegmentFilter
{
    const LEAD_OBJECT = 'lead';
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
     * @var string|array|bool|float|null
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

    /** @var LeadSegmentFilterDescriptor $translator */
    private $translator;

    /**
     * @var array
     */
    private $queryDescription = null;

    public function __construct(array $filter)
    {
        $this->glue    = isset($filter['glue']) ? $filter['glue'] : null;
        $this->field   = isset($filter['field']) ? $filter['field'] : null;
        $this->object  = isset($filter['object']) ? $filter['object'] : self::LEAD_OBJECT;
        $this->type    = isset($filter['type']) ? $filter['type'] : null;
        $this->display = isset($filter['display']) ? $filter['display'] : null;

        $operatorValue = isset($filter['operator']) ? $filter['operator'] : null;
        $this->setOperator($operatorValue);

        $filterValue = isset($filter['filter']) ? $filter['filter'] : null;
        $this->setFilter($filterValue);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function getSQLOperator()
    {
        switch ($this->getOperator()) {
            case 'gt':
                return '>';
            case 'eq':
                return '=';
            case 'gt':
                return '>';
            case 'gte':
                return '>=';
            case 'lt':
                return '<';
            case 'lte':
                return '<=';
        }
        throw new \Exception(sprintf('Unknown operator \'%s\'.', $filter->getOperator()));
    }

    public function getFilterConditionValue($argument = null) {
        switch ($this->getType()) {
            case 'number':
                return ":" . $argument;
            case 'datetime':
                return sprintf('":%s"', $argument);
            default:
                var_dump($dbColumn->getType());
                die();
        }
        throw new \Exception(sprintf('Unknown value type \'%s\'.', $filter->getType()));
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
     * @param string|array|bool|float|null $filter
     */
    public function setFilter($filter)
    {
        $filter = $this->sanitizeFilter($filter);

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

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'glue'     => $this->getGlue(),
            'field'    => $this->getField(),
            'object'   => $this->getObject(),
            'type'     => $this->getType(),
            'filter'   => $this->getFilter(),
            'display'  => $this->getDisplay(),
            'operator' => $this->getOperator(),
            'func'     => $this->getFunc(),
        ];
    }

    /**
     * @param string|array|bool|float|null $filter
     *
     * @return string|array|bool|float|null
     */
    private function sanitizeFilter($filter)
    {
        if ($filter === null || is_array($filter) || !$this->getType()) {
            return $filter;
        }

        switch ($this->getType()) {
            case 'number':
                $filter = (float)$filter;
                break;

            case 'boolean':
                $filter = (bool)$filter;
                break;
        }

        return $filter;
    }

    /**
     * @return array
     */
    public function getQueryDescription($translator = null)
    {
        $this->translator = is_null($translator) ? new LeadSegmentFilterDescriptor() : $translator;

        if (is_null($this->queryDescription)) {
            $this->assembleQueryDescription();
        }
        return $this->queryDescription;
    }

    /**
     * @param array $queryDescription
     * @return LeadSegmentFilter
     */
    public function setQueryDescription($queryDescription)
    {
        $this->queryDescription = $queryDescription;
        return $this;
    }

    /**
     * @return $this
     */
    private function assembleQueryDescription() {

        $this->queryDescription = isset($this->translator[$this->getField()])
            ? $this->translator[$this->getField()]
            : false;

        return $this;
    }
}
