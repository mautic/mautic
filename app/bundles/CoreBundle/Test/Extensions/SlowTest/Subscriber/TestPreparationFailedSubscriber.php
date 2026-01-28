<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Extensions\SlowTest\Subscriber;

use PHPUnit\Event\Test\PreparationFailed;
use PHPUnit\Event\Test\PreparationFailedSubscriber;

final class TestPreparationFailedSubscriber extends Subscriber implements PreparationFailedSubscriber
{
    public function notify(PreparationFailed $event): void
    {
        $this->slowTest()->testPreparationFailed();
    }
}
