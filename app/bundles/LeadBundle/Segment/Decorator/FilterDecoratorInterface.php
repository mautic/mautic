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

/**
 * Interface FilterDecoratorInterface.
 *
 * @TODO @petr document these functions please with phpdoc and meaningful description
 */
interface FilterDecoratorInterface
{
    public function getField(ContactSegmentFilterCrate $contactSegmentFilterCrate);

    public function getTable(ContactSegmentFilterCrate $contactSegmentFilterCrate);

    public function getOperator(ContactSegmentFilterCrate $contactSegmentFilterCrate);

    public function getParameterHolder(ContactSegmentFilterCrate $contactSegmentFilterCrate, $argument);

    public function getParameterValue(ContactSegmentFilterCrate $contactSegmentFilterCrate);

    public function getQueryType(ContactSegmentFilterCrate $contactSegmentFilterCrate);

    public function getAggregateFunc(ContactSegmentFilterCrate $contactSegmentFilterCrate);

    public function getWhere(ContactSegmentFilterCrate $contactSegmentFilterCrate);
}
