<?php

namespace Mautic\CoreBundle\Tests\Unit\Helper\TestResources;

class WakeupCall
{
    public function __wakeup()
    {
        throw new \Exception('this should not have been executed');
    }

    public function hello()
    {
        return 'test';
    }
}
