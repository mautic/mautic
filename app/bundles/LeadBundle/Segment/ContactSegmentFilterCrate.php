<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment;

class ContactSegmentFilterCrate
{
    const CONTACT_OBJECT = 'lead';
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
    private $operator;

    /**
     * @var array
     */
    private $sourceArray;

    /**
     * @var
     */
    private $nullValue;

    /**
     * ContactSegmentFilterCrate constructor.
     *
     * @param array $filter
     */
    public function __construct(array $filter)
    {
        $this->glue        = isset($filter['glue']) ? $filter['glue'] : null;
        $this->field       = isset($filter['field']) ? $filter['field'] : null;
        $this->object      = isset($filter['object']) ? $filter['object'] : self::CONTACT_OBJECT;
        $this->type        = isset($filter['type']) ? $filter['type'] : null;
        $this->filter      = isset($filter['filter']) ? $filter['filter'] : null;
        $this->nullValue   = isset($filter['null_value']) ? $filter['null_value'] : null;
        $this->sourceArray = $filter;

        $this->setOperator($filter);
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
     * @return bool
     */
    public function isContactType()
    {
        return self::CONTACT_OBJECT === $this->object;
    }

    /**
     * @return bool
     */
    public function isCompanyType()
    {
        return self::COMPANY_OBJECT === $this->object;
    }

    /**
     * @return string|array|bool|float|null
     */
    public function getFilter()
    {
        switch ($this->getType()) {
            case 'number':
                return (float) $this->filter;
            case 'boolean':
                return (bool) $this->filter;
        }

        return $this->filter;
    }

    /**
     * @return string|null
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return bool
     */
    public function isBooleanType()
    {
        return 'boolean' === $this->getType();
    }

    /**
     * @return bool
     */
    public function isNumberType()
    {
        return 'number' === $this->getType();
    }

    /**
     * @return bool
     */
    public function isDateType()
    {
        return 'date' === $this->getType() || $this->hasTimeParts();
    }

    /**
     * @return bool
     */
    public function hasTimeParts()
    {
        return 'datetime' === $this->getType();
    }

    /**
     * Filter value could be used directly - no modification (like regex etc.) needed.
     *
     * @return bool
     */
    public function filterValueDoNotNeedAdjustment()
    {
        return $this->isNumberType() || $this->isBooleanType();
    }

    /**
     * @return string|null
     */
    private function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getArray()
    {
        return $this->sourceArray;
    }

    /**
     * @param array $filter
     */
    private function setOperator(array $filter)
    {
        $operator = isset($filter['operator']) ? $filter['operator'] : null;

        if ('multiselect' === $this->getType() && in_array($operator, ['in', '!in'])) {
            $neg            = strpos($operator, '!') === false ? '' : '!';
            $this->operator = $neg.$this->getType();

            return;
        }
        if ('page_id' === $this->getField() || 'email_id' === $this->getField() || 'redirect_id' === $this->getField() || 'notification' === $this->getField()) {
            $operator = ($operator === '=') === $this->getFilter() ? 'notEmpty' : 'empty';
        }

        if ('=' === $operator && is_array($this->getFilter())) { //Fix for old segments which can have stored = instead on in operator
            $operator = 'in';
        }

        $this->operator = $operator;
    }

    /**
     * @return mixed
     */
    public function getNullValue()
    {
        return $this->nullValue;
    }
}
