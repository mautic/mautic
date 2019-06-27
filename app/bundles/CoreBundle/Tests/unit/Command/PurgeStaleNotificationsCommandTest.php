<?php

namespace Mautic\CoreBundle\Tests\Command;

use Mautic\CoreBundle\Command\PurgeStaleNotificationsCommand;
use PHPUnit\Framework\TestCase;

class PurgeStaleNotificationsCommandTest extends TestCase
{
    public function testFudge()
    {
        $cmd = new PurgeStaleNotificationsCommand();
    }
}
