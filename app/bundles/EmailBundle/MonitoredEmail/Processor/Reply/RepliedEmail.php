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
    public function __construct(private $fromAddress, private ?string $statHash = null)
    {
    }

    /**
     * @return string
     */
    public function getFromAddress()
    {
        return $this->fromAddress;
    }

    public function getStatHash(): ?string
    {
        return $this->statHash;
    }
}
