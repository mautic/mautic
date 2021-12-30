<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://www.mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ReportBundle\Entity\Report;
use PHPUnit\Framework\Assert;

class ReportControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testDescriptionIsNotEscaped()
    {
        $report = new Report();
        $report->setName('HTML Test');
        $report->setDescription('<b>This is allowed HTML</b>');
        $report->setSource('email');
        $this->container->get('mautic.report.model.report')->saveEntity($report);

        // Check the details page
        $this->client->request('GET', '/s/reports/'.$report->getId());
        $clientResponse        = $this->client->getResponse();
        $clientResponseContent = $clientResponse->getContent();
        $this->assertStringContainsString('<small><b>This is allowed HTML</b></small>', $clientResponseContent);

        // Check the list
        $this->client->request('GET', '/s/reports');
        $clientResponse        = $this->client->getResponse();
        $clientResponseContent = $clientResponse->getContent();
        $this->assertStringContainsString('<small><b>This is allowed HTML</b></small>', $clientResponseContent);
    }

    public function testContactReportwithComanyDateAddedColumn()
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

        $this->container->get('mautic.report.model.report')->saveEntity($report);

        // Check the details page
        $this->client->request('GET', '/s/reports/'.$report->getId());
        Assert::assertTrue($this->client->getResponse()->isOk());
    }
}
