<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Reply;

class RepliedEmail
{
    /**
     * @var string
     */
    private $fromAddress;

    /**
     * @var string|null
     */
    private $statHash;

    /**
     * RepliedEmail constructor.
     *
     * @param string $fromAddress
     * @param null   $statHash
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
     * @return string|null
     */
    public function getStatHash()
    {
        return $this->statHash;
    }
}
