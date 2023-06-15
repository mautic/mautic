<?php

namespace Mautic\ReportBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\ReportBundle\Entity\Report;
use PHPUnit\Framework\Assert;

class ReportControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testContactReportwithComanyDateAddedColumn(): void
    {
        $report = new Report();
        $report->setName('Contact report');
        $report->setDescription('<b>This is allowed HTML</b>');
        $report->setSource('leads');
        $coulmns = [
            'l.firstname',
            'companies_lead.date_added',
        ];
        $report->setColumns($coulmns);

        $this->getContainer()->get('mautic.report.model.report')->saveEntity($report);

        // Check the details page
        $this->client->request('GET', '/s/reports/view/'.$report->getId());
        Assert::assertTrue($this->client->getResponse()->isOk());
    }

    public function testEmailReportWithAggregatedColumnsAndTotals(): void
    {
        $contactModel = self::$container->get('mautic.lead.model.lead');

        // Create and save contacts
        $payload = [
            [
                'email'   => 'test1@example.com',
                'company' => 'Test',
                'points'  => 50,
            ],
            [
                'email'   => 'test2@example.com',
                'company' => 'Test',
                'points'  => 25,
            ],
            [
                'email'   => 'test3@example.com',
                'company' => 'Test',
                'points'  => 123,
            ],
            [
                'email'   => 'test4@example.com',
                'company' => 'Example',
                'points'  => 1234,
            ],
            [
                'email'   => 'test5@example.com',
                'company' => 'Example Test',
                'points'  => 0,
            ],
            [
                'email'   => 'test6@example.com',
                'company' => 'Example Test',
                'points'  => -10,
            ],
        ];

        foreach ($payload as $item) {
            $contact = new Lead();
            $contact->setEmail($item['email']);
            $contact->setCompany($item['company']);
            $contact->setPoints($item['points']);

            $contactModel->saveEntity($contact);
        }

        // Create and save report
        $report = new Report();
        $report->setName('Company lead points report');
        $report->setSource('leads');
        $columns = [
            'l.company',
        ];
        $report->setColumns($columns);
        $report->setGroupBy(['l.company']);
        $report->setTableOrder([
            [
                'column'    => 'l.company',
                'direction' => 'ASC',
            ],
        ]);
        $report->setAggregators([
            [
                'column'    => 'l.points',
                'function'  => 'MIN',
            ],
            [
                'column'    => 'l.points',
                'function'  => 'MAX',
            ],
            [
                'column'    => 'l.points',
                'function'  => 'SUM',
            ],
            [
                'column'    => 'l.points',
                'function'  => 'COUNT',
            ],
            [
                'column'    => 'l.points',
                'function'  => 'AVG',
            ],
        ]);
        self::$container->get('mautic.report.model.report')->saveEntity($report);

        // Expected report table values [ID, Company name, MIN Points, Max Points, SUM Points, COUNT Points, AVG Points]
        $expected = [
            ['1', 'Example', '1234', '1234', '1234', '1', '1234.0000'],
            ['2', 'Example Test', '-10', '0', '-10', '2', '-5.0000'],
            ['3', 'Test', '25', '123', '198', '3', '66.0000'],
            ['Totals', '&nbsp;',  '-10', '1234', '1422', '6', '431.6667'],
        ];

        // Get report view
        $this->client->request('GET', '/s/reports/view/'.$report->getId());
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk());

        // Load view content as HTML and convert the report table to result array
        $result  = [];
        $content = $response->getContent();
        $dom     = new \DOMDocument('1.0', 'utf-8');
        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR);
        $tbody = $dom->getElementById('reportTable')->getElementsByTagName('tbody')[0];
        $rows  = $tbody->getElementsByTagName('tr');

        for ($i = 0; $i < count($rows); ++$i) {
            $cells = $rows[$i]->getElementsByTagName('td');
            foreach ($cells as $c) {
                $result[$i][] = htmlentities(trim($c->nodeValue));
            }
        }

        Assert::assertSame($expected, $result);
        Assert::assertCount($tbody->childElementCount, $expected);
    }
}
