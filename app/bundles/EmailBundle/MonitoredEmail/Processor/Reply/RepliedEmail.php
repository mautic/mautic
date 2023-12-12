<?php

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Reply;

class RepliedEmail
{
    /**
     * @param string $fromAddress
     */
    public function __construct(
        private $fromAddress,
        private $statHash = null
    ) {
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
