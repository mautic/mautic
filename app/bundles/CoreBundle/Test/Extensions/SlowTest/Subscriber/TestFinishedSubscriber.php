<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Extensions\SlowTest\Subscriber;

use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;

final class TestFinishedSubscriber extends Subscriber implements FinishedSubscriber
{
    public function notify(Finished $event): void
    {
        $this->slowTest()->testFinished($event);
    }
}
