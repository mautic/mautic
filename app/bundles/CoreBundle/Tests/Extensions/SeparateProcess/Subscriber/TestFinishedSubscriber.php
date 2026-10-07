<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Extensions\SeparateProcess\Subscriber;

use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;

final class TestFinishedSubscriber extends Subscriber implements FinishedSubscriber
{
    public function notify(Finished $event): void
    {
        $this->separateProcess()->testFinished($event);
    }
}
