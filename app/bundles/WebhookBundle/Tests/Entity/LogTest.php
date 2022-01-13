<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
