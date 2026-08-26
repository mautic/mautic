<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\MonitoredEmail\Processor\Reply;

final class RepliedEmail
{
    /**
     * @param string $fromAddress
     */
    public function __construct(
        private $fromAddress,
        private ?string $statHash = null,
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
