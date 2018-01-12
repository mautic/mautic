<?php
/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\FilterQueryBuilder;

use Mautic\LeadBundle\Segment\LeadSegmentFilter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;

interface FilterQueryBuilderInterface
{
    /**
     * @param QueryBuilder      $queryBuilder
     * @param LeadSegmentFilter $filter
     *
     * @return QueryBuilder
     */
    public function applyQuery(QueryBuilder $queryBuilder, LeadSegmentFilter $filter);

    public static function getServiceId();
}
