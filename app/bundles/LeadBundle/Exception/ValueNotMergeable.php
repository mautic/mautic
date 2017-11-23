<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Exception;

class ValueNotMergeable extends \Exception
{
    /**
     * ValueNotMergeable constructor.
     *
     * @param mixed $newerValue
     * @param mixed $olderValue
     */
    public function __construct($newerValue, $olderValue)
    {
        parent::__construct(var_export($newerValue, true). ' / '.var_export($olderValue, true));
    }
}