<?php

/*
 * @copyright   2022 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\SmsBundle\Event;

use Symfony\Component\EventDispatcher\Event;

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
