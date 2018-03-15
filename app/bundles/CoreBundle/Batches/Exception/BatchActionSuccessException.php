<?php

namespace Mautic\CoreBundle\Batches\Exception;

/**
 * Exception for handling success of an action run. Here should be set result attributions.
 *
 * @author David Vurbs <david.vurbs@mautic.com>
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