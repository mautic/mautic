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
     * @var string
     */
    private $requiresBetween;

    /**
     * @param string $originalValue
     * @param string $requiresBetween
     */
    public function __construct($originalValue, $requiresBetween)
    {
        $this->originalValue   = $originalValue;
        $this->requiresBetween = $requiresBetween;
    }

    /**
     * @return string|array
     */
    public function getDateValue()
    {
        return $this->requiresBetween ? [$this->originalValue, $this->originalValue] : $this->originalValue;
    }
}
