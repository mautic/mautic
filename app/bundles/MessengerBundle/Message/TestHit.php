<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Message;

class TestHit
{
    public function __construct(
        public int $userId
    ) {
    }
}
