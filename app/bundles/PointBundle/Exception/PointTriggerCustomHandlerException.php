<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Exception;

class PointTriggerCustomHandlerException extends \Exception
{
    /**
     * @var bool
     */
    private $canChagePoints;

    /**
     * PointTriggerCustomHandlerException constructor.
     *
     * @param bool   $canChagePoints
     * @param string $message
     */
    public function __construct(bool $canChagePoints, $message = 'Custom trigger handled')
    {
        $this->canChagePoints = $canChagePoints;
        parent::__construct($message);
    }

    /**
     * @return boolean
     */
    public function canChangePoints()
    {
        return $this->canChagePoints;
    }
}
