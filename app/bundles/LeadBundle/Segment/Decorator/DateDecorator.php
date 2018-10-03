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

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Query\Filter\ComplexRelationValueFilterQueryBuilder;

/**
 * Class DateDecorator.
 */
class DateDecorator extends CustomMappedDecorator
{
    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @throws \Exception
     */
    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        throw new \Exception('Instance of Date option needs to implement this function');
    }

    /**
     * @return DateTimeHelper
     */
    public function getDefaultDate()
    {
        return new DateTimeHelper('midnight today', null, 'local');
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return string
     */
    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        if ($contactSegmentFilterCrate->isCompanyType()) {
            return ComplexRelationValueFilterQueryBuilder::getServiceId();
        }

        return parent::getQueryType($contactSegmentFilterCrate);
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return string
     */
    public function getRelationJoinTable(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        if ($contactSegmentFilterCrate->isCompanyType()) {
            return MAUTIC_TABLE_PREFIX.'companies_leads';
        }

        return null;
    }

    /**
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return string
     */
    public function getRelationJoinTableField(ContactSegmentFilterCrate $contactSegmentFilterCrate)
    {
        if ($contactSegmentFilterCrate->isCompanyType()) {
            return 'company_id';
        }

        return null;
    }
}
