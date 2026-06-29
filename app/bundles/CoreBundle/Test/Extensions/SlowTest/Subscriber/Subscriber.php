<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Extensions\SlowTest\Subscriber;

use Mautic\CoreBundle\Test\Extensions\SlowTest\SlowTest;

abstract class Subscriber
{
    public function __construct(private SlowTest $slowTest)
    {
    }

    public function slowTest(): SlowTest
    {
        return $this->slowTest;
    }
}
