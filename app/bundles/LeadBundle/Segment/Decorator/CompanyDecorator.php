<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Query\Filter\ComplexRelationValueFilterQueryBuilder;

/**
 * Class CustomMappedDecorator.
 */
class CompanyDecorator extends BaseDecorator
{
    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return null|string
     */
    public function getField(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        $originalField = $contactSegmentFilterCrate->getField();

        if (empty($this->dictionary[$originalField]['field'])) {
            return parent::getField($contactSegmentFilterCrate);
        }

        return $this->dictionary[$originalField]['field'];
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return string
     */
    public function getTable(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        $originalField = $contactSegmentFilterCrate->getField();

        if (empty($this->dictionary[$originalField]['foreign_table'])) {
            return parent::getTable($contactSegmentFilterCrate);
        }

        return MAUTIC_TABLE_PREFIX.$this->dictionary[$originalField]['foreign_table'];
    }

    /**
     * @return string
     */
    public function getRelationJoinTable()
    {
        return MAUTIC_TABLE_PREFIX.'companies_leads';
    }

    /**
     * @return string
     */
    public function getRelationJoinTableField()
    {
        return 'company_id';
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return string
     */
    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        return ComplexRelationValueFilterQueryBuilder::getServiceId();
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return string|bool if no func needed
     */
    public function getAggregateFunc(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        $originalField = $contactSegmentFilterCrate->getField();

        return isset($this->dictionary[$originalField]['func']) ?
            $this->dictionary[$originalField]['func'] : false;
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return \Mautic\LeadBundle\Segment\Query\Expression\CompositeExpression|null|string
     */
    public function getWhere(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        $originalField = $contactSegmentFilterCrate->getField();

        if (!isset($this->dictionary[$originalField]['where'])) {
            return parent::getWhere($contactSegmentFilterCrate);
        }

        return $this->dictionary[$originalField]['where'];
    }
}
