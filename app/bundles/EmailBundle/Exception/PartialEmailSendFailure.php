<?php

namespace Mautic\EmailBundle\Exception;

use Throwable;

class PartialEmailSendFailure extends \Exception
{
    /**
     * @var int
     */
    private $sentCount = 0;

    /**
     * PartialEmailSendFailure constructor.
     *
     * @param int    $sentCount
     * @param string $failureReason
     * @param int    $code
     */
    public function __construct($sentCount, $failureReason, $code = 0, Throwable $previous = null)
    {
        $this->sentCount = (int) $sentCount;

        parent::__construct($failureReason, $code, $previous);
    }

    /**
     * @return int
     */
    public function getSentCount()
    {
        return $this->sentCount;
    }
}
