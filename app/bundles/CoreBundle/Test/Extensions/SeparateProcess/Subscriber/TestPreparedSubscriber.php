<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Extensions\SeparateProcess\Subscriber;

use PHPUnit\Event\Test\Prepared;
use PHPUnit\Event\Test\PreparedSubscriber;

final class TestPreparedSubscriber extends Subscriber implements PreparedSubscriber
{
    public function notify(Prepared $event): void
    {
        $this->separateProcess()->testPrepared();
    }
}
