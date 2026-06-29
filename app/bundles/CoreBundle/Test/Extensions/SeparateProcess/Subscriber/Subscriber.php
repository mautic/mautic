<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Test\Extensions\SeparateProcess\Subscriber;

use Mautic\CoreBundle\Test\Extensions\SeparateProcess\SeparateProcess;

abstract class Subscriber
{
    public function __construct(private SeparateProcess $separateProcess)
    {
    }

    public function separateProcess(): SeparateProcess
    {
        return $this->separateProcess;
    }
}
