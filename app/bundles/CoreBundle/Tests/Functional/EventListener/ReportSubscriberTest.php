<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ReportSubscriberTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        if ('testCreatingAuditLogReportForNonAdminUser' === $this->getName()) {
            $this->clientServer = [
                'PHP_AUTH_USER' => 'sales',
                'PHP_AUTH_PW'   => 'mautic',
            ];
        }

        parent::setUp();
    }

    public function testCreatingAuditLogReportForNonAdminUser(): void
    {
        $this->client->request(Request::METHOD_POST, '/api/reports/new', ['name' => 'Audit Log', 'source' => 'audit.log']);
        $this->assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode(), $this->client->getResponse()->getContent());
    }

    /**
     * @dataProvider dataProvider
     *
     * @param mixed[] $filters
     */
    public function testReportViaApi(array $filters, int $segmentCount, int $companyCount, int $reportCount): void
    {
        $segmentData = $this->createSegment();
        $companyData = $this->createCompany();
        $reportId    = $this->createAuditLogReport($filters);

        $this->client->request(Request::METHOD_GET, "/api/reports/{$reportId}");
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $clientResponse->getStatusCode(), $clientResponse->getContent());
        $report = json_decode($clientResponse->getContent(), true);

        $this->assertSame('Audit Log', $report['report']['name']);
        $this->assertSame('audit.log', $report['report']['source']);
        $this->assertSame(
            [
                'action'     => 'al.action',
                'date_added' => 'al.date_added',
                'details'    => 'al.details',
                'ip_address' => 'al.ip_address',
                'object'     => 'al.object',
                'object_id'  => 'al.object_id',
                'user_id'    => 'al.user_id',
                'user_name'  => 'al.user_name',
            ],
            $report['dataColumns']
        );

        Assert::assertCount($segmentCount, array_filter($report['data'], static fn (array $row) => 'create' === $row['action'] && 'segment' === $row['object'] && (int) $row['object_id'] === $segmentData['list']['id']));
        Assert::assertCount($companyCount, array_filter($report['data'], static fn (array $row) => 'create' === $row['action'] && 'company' === $row['object'] && (int) $row['object_id'] === $companyData['company']['id']));
        Assert::assertCount($reportCount, array_filter($report['data'], static fn (array $row) => 'create' === $row['action'] && 'report' === $row['object'] && (int) $row['object_id'] === $report['report']['id']));
    }

    /**
     * @return iterable<string, mixed[]>
     */
    public function dataProvider(): iterable
    {
        yield 'No Filters' => [[], 1, 1, 1];

        yield 'Filter on object = company' => [[
            [
                'column'    => 'al.object',
                'glue'      => 'and',
                'value'     => 'company',
                'condition' => 'eq',
            ],
        ], 0, 1, 0];

        yield 'Filter on object = report' => [[
            [
                'column'    => 'al.object',
                'glue'      => 'and',
                'value'     => 'report',
                'condition' => 'eq',
            ],
        ], 0, 0, 1];
    }

    /**
     * @param array<array<string, string>> $filters
     */
    private function createAuditLogReport(array $filters = []): int
    {
        $payload = [
            'isPublished'  => true,
            'name'         => 'Audit Log',
            'system'       => false,
            'isScheduled'  => false,
            'source'       => 'audit.log',
            'columns'      => [
                'al.action',
                'al.date_added',
                'al.details',
                'al.ip_address',
                'al.object',
                'al.object_id',
                'al.user_id',
                'al.user_name',
            ],
            'filters'    => $filters,
            'tableOrder' => [],
            'graphs'     => [],
            'groupBy'    => [],
            'settings'   => [
            'showDynamicFilters'   => 0,
            'hideDateRangeFilter'  => 0,
            'showGraphsAboveTable' => 0,
            ],
            'aggregators' => [],
        ];

        $this->client->request(Request::METHOD_POST, '/api/reports/new', $payload);
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        return json_decode($clientResponse->getContent(), true)['report']['id'];
    }

    /**
     * @return mixed[]
     */
    public function createSegment(): array
    {
        $payload = [
            'name'        => 'API segment A',
            'description' => 'Segment created via API test',
        ];

        $this->client->request(Request::METHOD_POST, '/api/segments/new', $payload);
        $clientResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        return json_decode($clientResponse->getContent(), true);
    }

    /**
     * @return mixed[]
     */
    private function createCompany(): array
    {
        $payload = [
            'companyname'     => 'Company A',
            'companyemail'    => 'test@company.com',
            'companycity'     => 'City',
            'companyaddress1' => 'Address one',
            'companyaddress2' => 'Address two',
            'companyphone'    => '123456789',
            'companywebsite'  => 'https://company.com',
        ];
        $this->client->request(Request::METHOD_POST, '/api/companies/new', $payload);
        $clientResponse = $this->client->getResponse();
        // It can respond both depending if it exists in the database or not.
        $this->assertTrue(in_array($clientResponse->getStatusCode(), [Response::HTTP_OK, Response::HTTP_CREATED]), $clientResponse->getContent());

        return json_decode($clientResponse->getContent(), true);
    }
}
