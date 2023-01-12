<?php

namespace Mautic\SmsBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class TokensBuildEvent extends Event
{
    /**
     * @var array<int|string>
     */
    private array $tokens;

    /**
     * @param array<int|string> $tokens
     */
    public function __construct(array $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * @return array<int|string>
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    /**
     * @param array<int|string> $tokens
     */
    public function setTokens($tokens): void
    {
        $this->tokens = $tokens;
    }
}
