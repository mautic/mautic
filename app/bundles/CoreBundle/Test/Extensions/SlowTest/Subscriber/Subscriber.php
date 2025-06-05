<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Extensions\SlowTest\Subscriber;

use Mautic\CoreBundle\Test\Extensions\SlowTest\SlowTest;

abstract class Subscriber
{
    private SlowTest $slowTest;

    public function __construct(SlowTest $slowTest)
    {
        $this->slowTest = $slowTest;
    }

    public function slowTest(): SlowTest
    {
        return $this->slowTest;
    }
}
