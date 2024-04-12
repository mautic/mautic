<?php

namespace Mautic\SmsBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class TokensBuildEvent extends Event
{
    /**
     * @param array<string,array<int|string>> $tokens
     */
    public function __construct(private array $tokens)
    {
    }

    /**
     * @return array<string,array<int|string>>
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    /**
     * @param array<string,array<int|string>> $tokens
     */
    public function setTokens($tokens): void
    {
        $this->tokens = $tokens;
    }
}
