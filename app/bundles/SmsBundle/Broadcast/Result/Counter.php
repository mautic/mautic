<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Broadcast\Result;

class Counter
{
    /**
     * @var int
     */
    private $sentCount = 0;

    /**
     * @var int
     */
    private $failedCount = 0;

    public function sent()
    {
        ++$this->sentCount;
    }

    public function failed()
    {
        ++$this->failedCount;
    }

    /**
     * @return int
     */
    public function getSentCount()
    {
        return $this->sentCount;
    }

    /**
     * @return int
     */
    public function getFailedCount()
    {
        return $this->failedCount;
    }
}
