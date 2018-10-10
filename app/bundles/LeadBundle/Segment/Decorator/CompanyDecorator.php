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
 * Class CompanyDecorator.
 */
class CompanyDecorator extends BaseDecorator
{
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
}
