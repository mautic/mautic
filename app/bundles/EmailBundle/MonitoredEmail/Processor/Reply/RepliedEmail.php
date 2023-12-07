<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Reply;

class RepliedEmail
{
    /**
     * RepliedEmail constructor.
     *
     * @param string $fromAddress
     * @param null   $statHash
     */
    public function __construct(private $fromAddress, private $statHash = null)
    {
    }

    /**
     * @return string
     */
    public function getFromAddress()
    {
        return $this->fromAddress;
    }

    /**
     * @return string|null
     */
    public function getStatHash()
    {
        return $this->statHash;
    }
}
