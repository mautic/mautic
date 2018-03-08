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
     * ContactSegmentFilterCrate constructor.
     *
     * @param array $filter
     */
    public function __construct(array $filter)
    {
        $this->glue     = isset($filter['glue']) ? $filter['glue'] : null;
        $this->field    = isset($filter['field']) ? $filter['field'] : null;
        $this->object   = isset($filter['object']) ? $filter['object'] : self::CONTACT_OBJECT;
        $this->type     = isset($filter['type']) ? $filter['type'] : null;
        $this->operator = isset($filter['operator']) ? $filter['operator'] : null;
        $this->filter   = isset($filter['filter']) ? $filter['filter'] : null;
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
        return $this->object === self::CONTACT_OBJECT;
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
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return bool
     */
    public function isBooleanType()
    {
        return $this->getType() === 'boolean';
    }

    /**
     * @return bool
     */
    public function isDateType()
    {
        return $this->getType() === 'date' || $this->hasTimeParts();
    }

    /**
     * @return bool
     */
    public function hasTimeParts()
    {
        return $this->getType() === 'datetime';
    }
}
