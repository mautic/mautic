<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Entity;

use Mautic\EmailBundle\Entity\Email;

class EmailTest extends \PHPUnit\Framework\TestCase
{
    /**
     * test read count is setted.
     */
    public function testSetReadCount(): void
    {
        $readCount = 10;
        $email     = new Email();
        $email->setReadCount($readCount);

        $this->assertEquals($readCount, $email->getReadCount());
    }

    /**
     * test calculation of ctr percentage.
     */
    public function testCtrCalculation(): void
    {
        $ctr   = 80;
        $email = new Email();
        $email->setSentCount(5);

        $this->assertEquals($ctr, $email->getCtrPercentage(4));
    }

    /**
     * test email click counters.
     */
    public function testEmailClickCounters(): void
    {
        $email      = new Email();
        $trackables = [
            [
                'hits'        => 3,
                'unique_hits' => 2,
            ],
            [
                'hits'        => 4,
                'unique_hits' => 1,
            ],
            [
                'hits'        => 8,
                'unique_hits' => 3,
            ],
            [
                'hits'        => 0,
                'unique_hits' => 0,
            ],
        ];

        $this->assertEquals([15, 6], $email->getEmailClickCounters($trackables));
    }
}
