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

interface FilterDecoratorInterface
{
    /**
     * Returns filter's field (usually a column name in DB).
     *
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return null|string
     */
    public function getField(ContactSegmentFilterCrate $contactSegmentFilterCrate);

    /**
     * Returns DB table.
     *
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return string
     */
    public function getTable(ContactSegmentFilterCrate $contactSegmentFilterCrate);

    /**
     * Returns a string operator (like, eq, neq, ...).
     *
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return string
     */
    public function getOperator(ContactSegmentFilterCrate $contactSegmentFilterCrate);

    /**
     * Returns an argument for QueryBuilder (usually ':arg' in case that $argument is equal to 'arg' string.
     *
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     * @param array|string              $argument
     *
     * @return array|string
     */
    public function getParameterHolder(ContactSegmentFilterCrate $contactSegmentFilterCrate, $argument);

    /**
     * Returns formatted value for QueryBuilder ('%value%' for 'like', '%value' for 'Ends with', SQL-formatted date etc.).
     *
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return array|bool|float|null|string
     */
    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate);

    /**
     * Returns QueryBuilder's service name from the container.
     *
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return string
     */
    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate);

    /**
     * Returns a name of aggregation function for SQL (SUM, COUNT etc.)
     * Returns false if no aggregation function is needed.
     *
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return string|bool if no func needed
     */
    public function getAggregateFunc(ContactSegmentFilterCrate $contactSegmentFilterCrate);

    /**
     * Returns a special where condition which is needed to be added to QueryBuilder (like email_stats.is_read = 1 for 'Read emails')
     * Returns null if no special condition is needed.
     *
     * @param ContactSegmentFilterCrate $contactSegmentFilterCrate
     *
     * @return \Mautic\LeadBundle\Segment\Query\Expression\CompositeExpression|null|string
     */
    public function getWhere(ContactSegmentFilterCrate $contactSegmentFilterCrate);
}
