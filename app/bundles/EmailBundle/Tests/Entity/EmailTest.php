<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Entity;

use Mautic\EmailBundle\Entity\Email;
use PHPUnit\Framework\Assert;

class EmailTest extends \PHPUnit\Framework\TestCase
{
    public function testSetReadCount()
    {
        $readCount = 10;
        $email     = new Email();
        $email->setReadCount($readCount);

        $this->assertEquals($readCount, $email->getReadCount());
    }

    public function testCtrCalculation()
    {
        $ctr   = 80;
        $email = new Email();
        $email->setSentCount(5);

        $this->assertEquals($ctr, $email->getCtrPercentage(4));
    }
}
