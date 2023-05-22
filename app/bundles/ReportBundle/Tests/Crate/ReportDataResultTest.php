<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Crate;

use Mautic\ReportBundle\Crate\ReportDataResult;
use Mautic\ReportBundle\Tests\Fixtures;

class ReportDataResultTest extends \PHPUnit\Framework\TestCase
{
    public function testValidData(): void
    {
        $reportDataResult = new ReportDataResult(Fixtures::getValidReportResult());

        $this->assertSame(Fixtures::getValidReportData(), $reportDataResult->getData());
        $this->assertSame(Fixtures::getValidReportHeaders(), $reportDataResult->getHeaders());
        $this->assertSame(Fixtures::getValidReportTotalResult(), $reportDataResult->getTotalResults());
        $this->assertSame(Fixtures::getStringType(), $reportDataResult->getType('city'));
        $this->assertSame(Fixtures::getDateType(), $reportDataResult->getType('date_identified'));
        $this->assertSame(Fixtures::getEmailType(), $reportDataResult->getType('email'));
    }

    public function testValidDataWithAggregatedColumns(): void
    {
        $reportDataResult = new ReportDataResult(Fixtures::getValidReportResultWithAggregatedColumns());

        $this->assertSame(Fixtures::getValidReportDataAggregatedColumns(), $reportDataResult->getData());
        $this->assertSame(Fixtures::getValidReportWithAggregatedColumnsHeaders(), $reportDataResult->getHeaders());
        $this->assertSame(Fixtures::getValidReportWithAggregatedColumnsTotalResult(), $reportDataResult->getTotalResults());
        $this->assertSame(Fixtures::getIntegerType(), $reportDataResult->getType('SUM es.is_read'));
        $this->assertSame(Fixtures::getFloatType(), $reportDataResult->getType('AVG es.is_read'));
    }

    public function testNoDataProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Keys 'data', 'dataColumns' and 'columns' have to be provided");

        $data = Fixtures::getValidReportResult();
        unset($data['data']);
        new ReportDataResult($data);
    }

    public function testNoDataColumnProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Keys 'data', 'dataColumns' and 'columns' have to be provided");

        $data = Fixtures::getValidReportResult();
        unset($data['dataColumns']);
        new ReportDataResult($data);
    }

    public function testNoColumnProvided(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Keys 'data', 'dataColumns' and 'columns' have to be provided");

        $data = Fixtures::getValidReportResult();
        unset($data['columns']);
        new ReportDataResult($data);
    }
}
