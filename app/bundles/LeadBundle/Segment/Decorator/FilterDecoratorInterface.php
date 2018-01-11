<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Decorator;

use Mautic\LeadBundle\Segment\LeadSegmentFilterCrate;

interface FilterDecoratorInterface
{
    public function getField(LeadSegmentFilterCrate $leadSegmentFilterCrate);

    public function getTable(LeadSegmentFilterCrate $leadSegmentFilterCrate);

    public function getOperator(LeadSegmentFilterCrate $leadSegmentFilterCrate);

    public function getParameterHolder(LeadSegmentFilterCrate $leadSegmentFilterCrate, $argument);

    public function getParameterValue(LeadSegmentFilterCrate $leadSegmentFilterCrate);
}
