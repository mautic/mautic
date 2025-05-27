<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Extensions\SlowTest\Subscriber;

use PHPUnit\Event\Test\Prepared;
use PHPUnit\Event\Test\PreparedSubscriber;

final class TestPreparedSubscriber extends Subscriber implements PreparedSubscriber
{
    public function notify(Prepared $event): void
    {
        $this->slowTest()->testPrepared();
    }
}
