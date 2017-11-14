<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Reply;

class RepliedEmail
{
    /**
     * @var string
     */
    protected $fromAddress;

    /**
     * @var null|string
     */
    protected $statHash;

    /**
     * RepliedEmail constructor.
     *
     * @param      $fromAddress
     * @param null $statHash
     */
    public function __construct($fromAddress, $statHash = null)
    {
        $this->fromAddress = $fromAddress;
        $this->statHash    = $statHash;
    }

    /**
     * @return string
     */
    public function getFromAddress()
    {
        return $this->fromAddress;
    }

    /**
     * @return null|string
     */
    public function getStatHash()
    {
        return $this->statHash;
    }
}
