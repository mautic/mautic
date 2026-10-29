<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Scheduler\Option;

use PHPUnit\Framework\TestCase;
use Mautic\ReportBundle\Scheduler\Option\ExportOption;

final class ExportOptionTest extends TestCase
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

        /** @var mixed $invalidReportId */
        $invalidReportId = 'string';

        new ExportOption($invalidReportId);
    }
}
