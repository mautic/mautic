<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper\TestResources;

final class WakeupCall
{
    public function __wakeup()
    {
        throw new \Exception('this should not have been executed');
    }

    public function hello(): string
    {
        return 'test';
    }
}
