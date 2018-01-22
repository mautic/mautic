<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Segment\Decorator\Date;

class DateDefault implements DateOptionsInterface
{
    /**
     * @var string
     */
    private $originalValue;

    /**
     * @param string $originalValue
     */
    public function __construct($originalValue)
    {
        $this->originalValue = $originalValue;
    }

    /**
     * @return string
     */
    public function getDateValue()
    {
        return $this->originalValue;
    }
}
