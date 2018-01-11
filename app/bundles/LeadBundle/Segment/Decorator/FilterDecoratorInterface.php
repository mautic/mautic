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
    public function getField();

    // $field, respektive preklad z queryDescription - $field

    public function getTable();

    // leads nebo company - na zaklade podminky isLeadType() nebo z prekladu foreign_table

    public function getOperator(LeadSegmentFilterCrate $leadSegmentFilterCrate);

    // To, co vrati LeadSegmentFilterOperator

    public function getParameterHolder($argument);

    // vrati ":$argument", pripadne pro like "%:$argument%", date between vrati pole: [$startWith, $endWith]

    public function getParameterValue();

    // aktualne $filter
}
