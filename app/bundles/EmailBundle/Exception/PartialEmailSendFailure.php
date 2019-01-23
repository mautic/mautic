<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
     * @param int            $sentCount
     * @param string         $failureReason
     * @param int            $code
     * @param Throwable|null $previous
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
