<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Batches\Exception;

/**
 * Exception for handling success of an action run. Here should be set result attributions.
 */
class BatchActionSuccessException extends \Exception
{
    /**
     * @var int
     */
    private $countProcessed;

    /**
     * Get count of processed objects
     *
     * @return int
     */
    public function getCountProcessed()
    {
        return $this->countProcessed;
    }

    /**
     * Set count of processed objects
     *
     * @param int $countProcessed
     */
    public function setCountProcessed($countProcessed)
    {
        $this->countProcessed = $countProcessed;
    }
}