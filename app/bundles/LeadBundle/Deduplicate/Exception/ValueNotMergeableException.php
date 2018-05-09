<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Deduplicate\Exception;

class ValueNotMergeableException extends \Exception
{
    /**
     * @var mixed
     */
    private $newerValue;

    /**
     * @var mixed
     */
    private $olderValue;

    /**
     * ValueNotMergeableException constructor.
     *
     * @param mixed $newerValue
     * @param mixed $olderValue
     */
    public function __construct($newerValue, $olderValue)
    {
        $this->newerValue = $newerValue;
        $this->olderValue = $olderValue;

        parent::__construct();
    }

    /**
     * @return mixed
     */
    public function getNewerValue()
    {
        return $this->newerValue;
    }

    /**
     * @return mixed
     */
    public function getOlderValue()
    {
        return $this->olderValue;
    }
}
