<?php


namespace Mautic\ReportBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
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
        $this->client->request('GET', '/s/reports/'.$report->getId());
        Assert::assertTrue($this->client->getResponse()->isOk());
    }
}
