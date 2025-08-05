<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ReportBundle\Entity\Report;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractReportSubscriberTestCase extends MauticMysqlTestCase
{
    protected $useCleanupRollback   = false;
    protected bool $authenticateApi = true;

    /**
     * @param array<int, string> $columns
     * @param array<int, array{
     *      column: string,
     *      glue: string,
     *      dynamic: string|null,
     *      condition: string,
     *      value: list<string>|int|string
     *  }> $filters
     * @param array<int, array{
     *       column: string,
     *       direction: string,
     *   }> $order
     */
    public function createReport(string $source, array $columns = [], array $filters = [], array $order = []): Report
    {
        $report = new Report();
        $report->setName('Test report');
        $report->setSource($source);
        $report->setColumns($columns);
        $report->setFilters($filters);
        $report->setTableOrder($order);
        $this->em->persist($report);
        $this->em->flush();

        return $report;
    }

    /**
     * Verifies report response against expected data.
     *
     * @param array<int, array<int, mixed>> $expected Array of expected row data where each row is an array of column values
     */
    public function verifyReport(int $reportId, array $expected): void
    {
        $crawler            = $this->client->request(Request::METHOD_GET, "/s/reports/view/$reportId");
        $this->assertTrue($this->client->getResponse()->isOk());
        $crawlerReportTable = $crawler->filterXPath('//table[@id="reportTable"]')->first();

        // convert HTML table to php array
        $crawlerReportTable = $this->domTableToArray($crawlerReportTable);

        // remove row numbers
        $resultReportTable = array_map(function ($subArray) {
            array_shift($subArray);

            return $subArray;
        }, $crawlerReportTable);

        $this->assertSame($expected, $resultReportTable);
    }

    /**
     * Verifies an API report response against expected data.
     *
     * @param int                           $reportId       The ID of the report to verify
     * @param array<int, array<int, mixed>> $expectedReport Array of expected row data where each row is an array of column values
     */
    public function verifyApiReport(int $reportId, array $expectedReport): void
    {
        // Test API response
        $this->client->request(Request::METHOD_GET, sprintf('/api/reports/%d', $reportId));
        $clientResponse = $this->client->getResponse();
        $result         = json_decode($clientResponse->getContent(), true);

        // Verify total results count
        $this->assertEquals(count($expectedReport), $result['totalResults']);

        $columnNames = $this->findApiReportCols($result['dataColumns'], $result['report']['columns']);

        // Transform expected data to match an API response format
        $transformedExpected = $this->transformExpectedApiData($expectedReport, $columnNames);

        // Compare transformed data with API response
        $this->assertEquals($transformedExpected, $result['data']);
    }

    /**
     * Finds matching column indices between data columns and report columns.
     *
     * @param array<int|string, string> $dataColumns
     * @param array<int|string, string> $reportColumns
     *
     * @return array<int, string> Array of matched column indices
     */
    private function findApiReportCols(array $dataColumns, array $reportColumns): array
    {
        $matches           = [];
        $dataColumnFlipped = array_flip($dataColumns);

        foreach ($reportColumns as $reportColumn) {
            if (isset($dataColumnFlipped[$reportColumn])) {
                $matches[$dataColumnFlipped[$reportColumn]] = $reportColumn;
            }
        }

        return array_keys($matches);
    }

    /**
     * Transforms the expected data array to match the API response format.
     *
     * @param array<int, array<int, mixed>> $expectedReport
     * @param array<int, string>            $columnNames
     *
     * @return array<int, array<string, mixed>> Transformed data matching API response format
     */
    private function transformExpectedApiData(array $expectedReport, array $columnNames): array
    {
        $transformedExpected = [];

        foreach ($expectedReport as $expectedRow) {
            $transformedExpectedRow = [];
            foreach ($columnNames as $index => $columnName) {
                $transformedExpectedRow[$columnName] = $expectedRow[$index] ?? null;
            }
            $transformedExpected[] = $transformedExpectedRow;
        }

        return $transformedExpected;
    }

    /**
     * @return array<int,array<int,mixed>>
     */
    private function domTableToArray(Crawler $crawler): array
    {
        $table = $crawler->filter('tr')->each(fn ($tr) => $tr->filter('td')->each(fn ($td) => trim($td->text())));
        array_shift($table);
        array_pop($table);

        return $table;
    }
}
