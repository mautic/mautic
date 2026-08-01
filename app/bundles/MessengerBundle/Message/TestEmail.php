<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Message;

final class TestEmail
{
    public function __construct(
        public int $userId,
    ) {
    }
}
