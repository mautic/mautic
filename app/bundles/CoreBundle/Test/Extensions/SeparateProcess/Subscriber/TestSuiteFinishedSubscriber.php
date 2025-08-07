<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Extensions\SeparateProcess\Subscriber;

use PHPUnit\Event\TestSuite\Finished;
use PHPUnit\Event\TestSuite\FinishedSubscriber;

final class TestSuiteFinishedSubscriber extends Subscriber implements FinishedSubscriber
{
    public function notify(Finished $event): void
    {
        $this->separateProcess()->testSuiteFinished($event);
    }
}
