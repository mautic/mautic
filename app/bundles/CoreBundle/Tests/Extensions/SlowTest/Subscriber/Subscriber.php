<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Extensions\SlowTest\Subscriber;

use Mautic\CoreBundle\Tests\Extensions\SlowTest\SlowTest;

abstract class Subscriber
{
    public function __construct(
        private readonly SlowTest $slowTest,
    ) {
    }

    public function slowTest(): SlowTest
    {
        return $this->slowTest;
    }
}
