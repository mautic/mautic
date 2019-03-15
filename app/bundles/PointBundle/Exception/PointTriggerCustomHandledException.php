<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Exception;

class PointTriggerCustomHandledException extends \Exception
{
    /**
     * @var bool
     */
    private $canChagePoints;

    public function __construct(bool $canChagePoints, $message = 'Trigger handled')
    {
        parent::__construct($message);
        $this->canChagePoints = $canChagePoints;
    }

    /**
     * @return boolean
     */
    public function canChangePoints()
    {
        return $this->canChagePoints;
    }
}
