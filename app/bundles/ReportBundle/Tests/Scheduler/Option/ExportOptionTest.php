<?php

namespace Mautic\ReportBundle\Tests\Scheduler\Option;

use Mautic\ReportBundle\Scheduler\Option\ExportOption;

class ExportOptionTest extends \PHPUnit\Framework\TestCase
{
    public function testReportId(): void
    {
        $exportOption = new ExportOption(11);

        $this->assertSame(11, $exportOption->getReportId());
    }

    public function testNoReportId(): void
    {
        $exportOption = new ExportOption(null);

        $this->assertSame(0, $exportOption->getReportId());
    }

    public function testBadFormatOfId(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new ExportOption('string');
    }
}
