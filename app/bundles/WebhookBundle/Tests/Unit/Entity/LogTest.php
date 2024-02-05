<?php

declare(strict_types=1);

namespace Mautic\WebhookBundle\Tests\Entity;

use Mautic\WebhookBundle\Entity\Log;

class LogTest extends \PHPUnit\Framework\TestCase
{
    public function testSetNote(): void
    {
        $log = new Log();
        $log->setNote("\x6d\x61\x75\x74\x69\x63");
        $this->assertSame('mautic', $log->getNote());

        $log->setNote("\x57\xfc\x72\x74\x74\x65\x6d\x62\x65\x72\x67");  // original string is W�rttemberg, in this '�' is invaliad char so it should be removed
        $this->assertSame('Wrttemberg', $log->getNote());

        $log->setNote('mautic');
        $this->assertSame('mautic', $log->getNote());

        $log->setNote('ěščřžýá');
        $this->assertSame('ěščřžýá', $log->getNote());

        $log->setNote('†º5¶2KfNœã');
        $this->assertSame('†º5¶2KfNœã', $log->getNote());
    }
}
