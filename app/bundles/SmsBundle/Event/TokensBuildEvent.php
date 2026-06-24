<?php

namespace Mautic\SmsBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class TokensBuildEvent extends Event
{
    /**
     * @param array<string, string> $tokens
     */
    public function __construct(private array $tokens)
    {
    }

    /**
     * @return array<string, string>
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    /**
     * @param array<string, string> $tokens
     */
    public function setTokens(array $tokens): void
    {
        $this->tokens = $tokens;
    }
}
