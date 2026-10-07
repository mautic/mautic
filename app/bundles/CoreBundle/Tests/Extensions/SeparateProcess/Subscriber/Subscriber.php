<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Extensions\SeparateProcess\Subscriber;

use Mautic\CoreBundle\Tests\Extensions\SeparateProcess\SeparateProcess;

abstract class Subscriber
{
    public function __construct(
        private readonly SeparateProcess $separateProcess,
    ) {
    }

    public function separateProcess(): SeparateProcess
    {
        return $this->separateProcess;
    }
}
