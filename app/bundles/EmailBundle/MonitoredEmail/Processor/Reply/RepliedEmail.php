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
        private readonly ?string $statHash = null,
    ) {
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
