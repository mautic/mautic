<?php

namespace Mautic\CoreBundle\Tests\unit\Helper\TestResources;

class WakeupCall
{
    public function __wakeup()
    {
        throw new \Exception('this should not have been executed');
    }
}
