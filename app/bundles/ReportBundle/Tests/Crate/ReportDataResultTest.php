<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Crate;

use Mautic\CoreBundle\Twig\Helper\FormatterHelper;
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

    public function testDataCount(): void
    {
        $fixture           = Fixtures::getValidReportResult();
        $reportDataResult  = new ReportDataResult($fixture);
        $expectedDataCount =  count($fixture['data']);
        $actualDataCount   = $reportDataResult->getDataCount();

        $fixture['data']       = [];
        $reportEmptyDataResult = new ReportDataResult($fixture);

        $this->assertSame($expectedDataCount, $actualDataCount);
        $this->assertSame(0, $reportEmptyDataResult->getDataCount());
    }

    public function testGetTotals(): void
    {
        $fixtureAggregatedColumns       = Fixtures::getValidReportResultWithAggregatedColumns();
        $fixtureAggregatedColumnsTotals = Fixtures::getValidReportDataAggregatedTotals();
        $reportDataResult               = new ReportDataResult($fixtureAggregatedColumns);

        $fixtureNoAggregatedColumns          = Fixtures::getValidReportResult();
        $reportDataResultNoAggregatedColumns = new ReportDataResult($fixtureNoAggregatedColumns);

        $this->assertSame($fixtureAggregatedColumnsTotals, $reportDataResult->getTotals());
        $this->assertEmpty($reportDataResultNoAggregatedColumns->getTotals());
    }

    public function testGetGraphs(): void
    {
        $reportDataResult          = new ReportDataResult(Fixtures::getValidReportResultWithNoGraphs());
        $reportDataResulWithGraphs = new ReportDataResult(Fixtures::getValidReportResultWithGraphs());
        $expectedGraphData         = [
            'mautic.email.graph.line.stats' => [
                'options'        => [],
                'dynamicFilters' => [],
                'paginate'       => true,
            ],
        ];

        $this->assertEmpty($reportDataResult->getGraphs());
        $this->assertEquals($expectedGraphData, $reportDataResulWithGraphs->getGraphs());
    }

    public function testGetDateFrom(): void
    {
        $reportDataResult = new ReportDataResult(Fixtures::getValidReportResult());
        $expectedDateFrom = Fixtures::getDateFrom();

        $this->assertEquals($expectedDateFrom, $reportDataResult->getDateFrom());
        $this->assertTrue($reportDataResult->getDateFrom() instanceof \DateTime);
    }

    public function testGetDateTo(): void
    {
        $reportDataResult = new ReportDataResult(Fixtures::getValidReportResult());
        $dateTo           = Fixtures::getDateTo();

        $this->assertEquals($dateTo, $reportDataResult->getDateTo());
        $this->assertTrue($reportDataResult->getDateTo() instanceof \DateTime);
    }

    public function testIsLastPage(): void
    {
        $reportDataResult                      = new ReportDataResult(Fixtures::getValidReportResult());
        $reportDataResultWithAggregatedColumns = new ReportDataResult(Fixtures::getValidReportResultWithAggregatedColumns());

        $this->assertTrue($reportDataResult->isLastPage());
        $this->assertTrue($reportDataResultWithAggregatedColumns->isLastPage());
    }

    public function testCalcTotal(): void
    {
        $reportDataResult = new ReportDataResult(Fixtures::getValidReportResult());
        $values           = ['1', '13', '4', '6', '20'];
        $valuesCount      = count($values);

        // Calc test COUNT
        $calc         = $reportDataResult->calcTotal('COUNT', $valuesCount, $values);
        $calcWithPrev = $reportDataResult->calcTotal('COUNT', $valuesCount, $values, 5);

        $this->assertEquals(array_sum($values), $calc);
        $this->assertEquals(array_sum($values) + 5, $calcWithPrev);

        // Calc test SUM
        $calc         = $reportDataResult->calcTotal('SUM', $valuesCount, $values);
        $calcWithPrev = $reportDataResult->calcTotal('SUM', $valuesCount, $values, 10);

        $this->assertEquals(array_sum($values), $calc);
        $this->assertEquals(array_sum($values) + 10, $calcWithPrev);

        // Calc test AVG
        $calc         = $reportDataResult->calcTotal('AVG', $valuesCount, $values);
        $calcWithPrev = $reportDataResult->calcTotal('AVG', $valuesCount + 2, $values, 10);

        $this->assertEquals(round(array_sum($values) / $valuesCount, FormatterHelper::FLOAT_PRECISION), $calc);
        $this->assertEquals(round((array_sum($values) + 10) / ($valuesCount + 2), FormatterHelper::FLOAT_PRECISION), $calcWithPrev);

        // Calc test MIN
        $calc         = $reportDataResult->calcTotal('MIN', $valuesCount, $values);
        $calcWithPrev = $reportDataResult->calcTotal('MIN', $valuesCount, $values, 0);

        $this->assertEquals(1, $calc);
        $this->assertEquals(0, $calcWithPrev);

        // Calc test MAX
        $calc         = $reportDataResult->calcTotal('MAX', $valuesCount, $values);
        $calcWithPrev = $reportDataResult->calcTotal('MAX', $valuesCount, $values, 25);

        $this->assertEquals(20, $calc);
        $this->assertEquals(25, $calcWithPrev);

        // Calc test 'default'
        $calc         = $reportDataResult->calcTotal('RANDOM', $valuesCount, $values);
        $calcWithPrev = $reportDataResult->calcTotal('RANDOM', $valuesCount, $values, 50);

        $this->assertNull($calc);
        $this->assertEquals(50, $calcWithPrev);
    }

    public function testGetColumnKeys(): void
    {
        $fixtures                              = Fixtures::getValidReportResultWithAggregatedColumns();
        $reportDataResultWithAggregatedColumns = new ReportDataResult($fixtures);

        unset($fixtures['data'][0]);
        $reportDataResultWithAggregatedColumnsInvalid = new ReportDataResult($fixtures);

        $this->assertEquals(Fixtures::getValidReportWithAggregatedColumnsKeys(), $reportDataResultWithAggregatedColumns->getColumnKeys());
        $this->assertEmpty($reportDataResultWithAggregatedColumnsInvalid->getColumnKeys());
    }
}
