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

class LeadSegmentFilters implements \Iterator, \Countable
{
    /**
     * @var int
     */
    private $position = 0;

    /**
     * @var array|LeadSegmentFilter[]
     */
    private $leadSegmentFilters = [];

    /**
     * @var bool
     */
    private $hasCompanyFilter = false;

    /**
     * @var bool
     */
    private $listFiltersInnerJoinCompany = false;

    public function addLeadSegmentFilter(LeadSegmentFilter $leadSegmentFilter)
    {
        $this->leadSegmentFilters[] = $leadSegmentFilter;
//        if ($leadSegmentFilter->isCompanyType()) {
//            $this->hasCompanyFilter = true;
//            // Must tell getLeadsByList how to best handle the relationship with the companies table
//            if (!in_array($leadSegmentFilter->getFunc(), ['empty', 'neq', 'notIn', 'notLike'], true)) {
//                $this->listFiltersInnerJoinCompany = true;
//            }
//        }
    }

    /**
     * Return the current element.
     *
     * @see  http://php.net/manual/en/iterator.current.php
     *
     * @return LeadSegmentFilter
     */
    public function current()
    {
        return $this->leadSegmentFilters[$this->position];
    }

    /**
     * Move forward to next element.
     *
     * @see  http://php.net/manual/en/iterator.next.php
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Return the key of the current element.
     *
     * @see  http://php.net/manual/en/iterator.key.php
     *
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Checks if current position is valid.
     *
     * @see  http://php.net/manual/en/iterator.valid.php
     *
     * @return bool
     */
    public function valid()
    {
        return isset($this->leadSegmentFilters[$this->position]);
    }

    /**
     * Rewind the Iterator to the first element.
     *
     * @see  http://php.net/manual/en/iterator.rewind.php
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Count elements of an object.
     *
     * @see  http://php.net/manual/en/countable.count.php
     *
     * @return int
     */
    public function count()
    {
        return count($this->leadSegmentFilters);
    }

    /**
     * @return bool
     */
    public function isHasCompanyFilter()
    {
        return $this->hasCompanyFilter;
    }

    /**
     * @return bool
     */
    public function isListFiltersInnerJoinCompany()
    {
        return $this->listFiltersInnerJoinCompany;
    }
}
