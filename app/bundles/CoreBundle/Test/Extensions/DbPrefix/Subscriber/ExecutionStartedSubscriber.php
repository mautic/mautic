<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Extensions\DbPrefix\Subscriber;

use PHPUnit\Event\TestRunner\ExecutionStarted;

class ExecutionStartedSubscriber extends Subscriber implements \PHPUnit\Event\TestRunner\ExecutionStartedSubscriber
{
    public function notify(ExecutionStarted $event): void
    {
        $this->dbPrefix()->defineDbPrefix();
    }
}
