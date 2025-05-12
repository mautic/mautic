<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Extensions\SlowTest\Subscriber;

use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;

final class TestPreparationStartedSubscriber extends Subscriber implements PreparationStartedSubscriber
{
    public function notify(PreparationStarted $event): void
    {
        $this->slowTest()->testPreparationStarted($event);
    }
}
